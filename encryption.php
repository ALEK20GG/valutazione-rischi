<?php
// encryption.php - Funzioni per crittografia dati sensibili

// Chiave di crittografia (IMPORTANTE: usa una variabile d'ambiente in produzione)
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'your-secret-encryption-key-change-this-32chars!!');

/**
 * Cripta un dato usando AES-256-CBC
 */
function encrypt_data(string $data): string {
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(16);
    
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    
    // Combina IV + encrypted data e codifica in base64
    return base64_encode($iv . $encrypted);
}

/**
 * Decripta un dato
 */
function decrypt_data(string $encrypted_data): string {
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $data = base64_decode($encrypted_data);
    
    // Estrai IV (primi 16 bytes) e dati criptati
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Hash sicuro per le password (usa bcrypt)
 * Questa funzione è già usata nel codice ma la rendiamo esplicita
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica password
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}