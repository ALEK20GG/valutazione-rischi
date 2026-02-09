<?php

/**
 * NIRoshCalculator
 *
 * Implementazione del calcolatore NIOSH utilizzato da `niosh_form.php`.
 * Calcola:
 *  - HM, VM, DM, AM, FM, CM
 *  - RWL (Recommended Weight Limit)
 *  - LI  (Lifting Index)
 */
class NIRoshCalculator
{
    /** Costante di carico NIOSH (kg) */
    private const LOAD_CONSTANT = 23.0;

    /** Distanza orizzontale ideale (cm) per HM */
    private const HM_IDEAL = 25.0;

    // -------------------------
    // Tabelle NIOSH prestabilite
    // -------------------------

    /**
     * Costante di peso per sesso/età (non usata nel calcolo RWL standard,
     * ma disponibile se serve una verifica aggiuntiva).
     */
    private const WEIGHT_CONSTANTS = [
        ['m', '18',    30],
        ['f', '18',    20],
        ['m', '15-18', 20],
        ['f', '15-18', 15],
    ];

    /** VM: altezza verticale dal suolo (cm) -> fattore */
    private const HEIGHT_THRESHOLDS = [ 0, 25, 50, 75, 100, 125, 150, 175 ];
    private const HEIGHT_FACTORS    = [ 0.78, 0.85, 0.93, 1.0, 0.93, 0.85, 0.78, 0 ];

    /** HM: distanza orizzontale (cm) -> fattore */
    private const HORIZ_THRESHOLDS = [ 25, 30, 40, 50, 70, 100, 170, "> 175" ];
    private const HORIZ_FACTORS    =  [ 1.0, 0.97, 0.93, 0.91, 0.88, 0.87, 0.86, 0 ];

    /** DM: distanza verticale di movimento (cm) -> fattore */
    private const DIST_THRESHOLDS = [ 25, 30, 40, 50, 55, 60, "> 63" ];
    private const DIST_FACTORS    = [ 1, 0.83, 0.63, 0.50, 0.45, 0.42, 0 ];

    /** AM: angolo asimmetrico (gradi) -> fattore */
    private const ANGLE_THRESHOLDS = [ 0, 30, 60, 90, 120, 135, "> 135"];
    private const ANGLE_FACTORS    = [ 1.0, 0.90, 0.81, 0.71, 0.62, 0.57, 0 ];

    /** FM: frequenza (sollevamenti/min) per durata -> fattore */
    private const FREQ_THRESHOLDS = [0.2, 1, 4, 6, 9, 12, 15];
    private const FREQ_FACTORS = [
        // < 1 ora
        'short' => [ 1, 0.94, 0.81, 0.75, 0.52, 0.37, 0 ],
        // 1–2 ore
        'moderate' => [ 0.95, 0.88, 0.72, 0.5, 0.3, 0.21, 0 ], 
        // 2–8 ore
        'long' => [ 0.85, 0.75, 0.45, 0.27, 0.15, 0, 0 ]
    ];

    // -------------------------
    // Input
    // -------------------------
    private float $weight;               // kg
    private float $horizontal_distance;  // H (cm)
    private float $vertical_height;      // V (cm, altezza dal suolo)
    private float $distance_moved;       // D (cm, spostamento verticale)
    private float $asymmetric_angle;     // A (gradi)
    private float $frequency;            // F (sollevamenti/min)
    private string $duration;            // 'short' | 'moderate' | 'long'
    private string $grip_quality;        // 'good' | 'fair' | 'poor'

    // Campo opzionale, tenuto per compatibilità con getResult()
    private float $vertical_distance = 0.0;

    // -------------------------
    // Moltiplicatori
    // -------------------------
    private float $hm = 0.0;
    private float $vm = 0.0;
    private float $dm = 0.0;
    private float $am = 0.0;
    private float $fm = 0.0;
    private float $cm = 0.0;

    // -------------------------
    // Risultati principali
    // -------------------------
    private float $rwl = 0.0;
    private float $li = 0.0;

    /**
     * Restituisce il fattore corrispondente al valore dato usando
     * una tabella soglia/fattore (valore <= soglia => fattore).
     */
    private static function lookupFactor(float $value, array $thresholds, array $factors): float
    {
        $count = min(count($thresholds), count($factors));

        for ($i = 0; $i < $count; $i++) {
            if ($value <= $thresholds[$i]) {
                return (float)$factors[$i];
            }
        }

        // Se il valore è oltre l'ultima soglia, usa l'ultimo fattore
        return (float)$factors[$count - 1];
    }

    public function __construct(
        float $weight,
        float $horizontal_distance,
        float $vertical_height,
        float $distance_moved,
        float $asymmetric_angle,
        float $frequency,
        string $duration,
        string $grip_quality
    ) {
        $this->weight = $weight;
        $this->horizontal_distance = $horizontal_distance;
        $this->vertical_height = $vertical_height;
        $this->distance_moved = $distance_moved;
        $this->asymmetric_angle = $asymmetric_angle;
        $this->frequency = $frequency;
        $this->duration = $duration;
        $this->grip_quality = $grip_quality;

        // Per ora teniamo vertical_distance a 0 per compatibilità
        $this->vertical_distance = 0.0;
    }

    /**
     * Calcola il Horizontal Multiplier (HM)
     * Usando la tabella NIOSH prestabilita (distanza orizzontale -> fattore)
     */
    private function calculateHM(): void
    {
        $H = $this->horizontal_distance;

        if ($H <= 0) {
            $this->hm = 0.0;
            return;
        }

        $this->hm = self::lookupFactor($H, self::HORIZ_THRESHOLDS, self::HORIZ_FACTORS);
    }

    /**
     * Calcola il Vertical Multiplier (VM)
     * Basato sull'altezza verticale dal suolo usando la tabella prestabilita
     */
    private function calculateVM(): void
    {
        $V = $this->vertical_height;

        if ($V < 0 || $V > 175) {
            $this->vm = 0.0;
            return;
        }

        $this->vm = self::lookupFactor($V, self::HEIGHT_THRESHOLDS, self::HEIGHT_FACTORS);
    }

    /**
     * Calcola il Distance Multiplier (DM)
     * Usando la tabella NIOSH prestabilita (distanza verticale di movimento -> fattore)
     */
    private function calculateDM(): void
    {
        $D = $this->distance_moved;

        if ($D <= 0) {
            $this->dm = 0.0;
            return;
        }

        $this->dm = self::lookupFactor($D, self::DIST_THRESHOLDS, self::DIST_FACTORS);
    }

    /**
     * Calcola l'Asymmetric Multiplier (AM)
     * Usando la tabella NIOSH prestabilita (angolo asimmetrico -> fattore)
     */
    private function calculateAM(): void
    {
        $A = $this->asymmetric_angle;

        if ($A < 0 || $A > 135) {
            $this->am = 0.0;
            return;
        }

        $this->am = self::lookupFactor($A, self::ANGLE_THRESHOLDS, self::ANGLE_FACTORS);
    }

    /**
     * Calcola il Frequency Multiplier (FM)
     * Basato sulla frequenza di sollevamento e durata dell'attività
     *
     * Viene utilizzata la tabella NIOSH prestabilita (frequenza x durata).
     */
    private function calculateFM(): void
    {
        $freq = $this->frequency;
        $duration = $this->duration; // 'short', 'moderate', 'long'

        $row = self::FREQ_FACTORS[$duration] ?? self::FREQ_FACTORS['moderate'];
        $this->fm = self::lookupFactor($freq, self::FREQ_THRESHOLDS, $row);
    }

    /**
     * Calcola il Coupling Multiplier (CM)
     * Basato sulla qualità della presa (grip)
     */
    private function calculateCM(): void
    {
        $grip = $this->grip_quality; // 'good', 'fair', 'poor'

        $cm_values = [
            'good' => 1.0,
            'poor' => 0.90,
        ];

        $this->cm = $cm_values[$grip] ?? 0.90;
    }

    /**
     * Esegue tutti i calcoli (HM, VM, DM, AM, FM, CM, RWL, LI)
     */
    public function calculate(): void
    {
        $this->calculateHM();
        $this->calculateVM();
        $this->calculateDM();
        $this->calculateAM();
        $this->calculateFM();
        $this->calculateCM();

        // RWL = LC × HM × VM × DM × AM × FM × CM
        $this->rwl = self::LOAD_CONSTANT
            * $this->hm
            * $this->vm
            * $this->dm
            * $this->am
            * $this->fm
            * $this->cm;

        // LI = Load / RWL
        $this->li = $this->rwl > 0.0 ? ($this->weight / $this->rwl) : 999.0;
    }

    /**
     * Ritorna il risultato come array
     */
    public function getResult(): array
    {
        return [
            'weight' => $this->weight,
            'horizontal_distance' => $this->horizontal_distance,
            'vertical_distance' => $this->vertical_distance,
            'vertical_height' => $this->vertical_height,
            'distance_moved' => $this->distance_moved,
            'asymmetric_angle' => $this->asymmetric_angle,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'grip_quality' => $this->grip_quality,
            'hm' => round($this->hm, 4),
            'vm' => round($this->vm, 4),
            'dm' => round($this->dm, 4),
            'am' => round($this->am, 4),
            'fm' => round($this->fm, 4),
            'cm' => round($this->cm, 4),
            'rwl' => round($this->rwl, 2),
            'li' => round($this->li, 2),
            'risk_level' => $this->getRiskLevel(),
            'risk_description' => $this->getRiskDescription(),
        ];
    }

    /**
     * Determina il livello di rischio in base al LI
     */
    private function getRiskLevel(): string
    {
        if ($this->li <= 1.0) {
            return 'basso';
        }

        if ($this->li <= 2.0) {
            return 'moderato';
        }

        if ($this->li <= 3.0) {
            return 'elevato';
        }

        return 'molto_elevato';
    }

    /**
     * Descrizione testuale del rischio
     */
    private function getRiskDescription(): string
    {
        $li = $this->li;

        if ($li <= 1.0) {
            return "Il carico è entro i limiti sicuri. La probabilità di lesioni è molto bassa.";
        }

        if ($li <= 2.0) {
            return "Il carico presenta un rischio moderato. Alcuni lavoratori potrebbero essere a rischio di lesioni.";
        }

        if ($li <= 3.0) {
            return "Il carico presenta un rischio elevato. Molti lavoratori potrebbero essere a rischio di lesioni lombari.";
        }

        return "Il carico presenta un rischio molto elevato. È fortemente consigliato implementare misure di controllo del rischio.";
    }
}