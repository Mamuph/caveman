<?php


class Model_SignatureManager
{


    /**
     * Exported key
     *
     * @var null
     */
    private $private_key = null;


    /**
     * Original private key as PEM string
     * @var null|string
     */
    private $pem_string = null;


    /**
     * Check if private key requires password
     *
     * @return bool
     */
    public function isProtected() : bool
    {
        return stripos($this->pem_string, 'Proc-Type: 4,ENCRYPTED');
    }


    /**
     * Set private key from an external file.
     *
     * @param $file
     * @throws Exception
     */
    public function setPrivateKeyFromFile($file)
    {
        if (!File::exists($file, File::SCOPE_EXTERNAL))
            throw new Exception("Private key file not found");

        $this->pem_string = file_get_contents($file);
    }


    /**
     * Export key.
     *
     * @param string $password
     * @return bool
     * @throws Exception
     */
    public function export($password = '') : bool
    {

        if (empty($this->pem_string))
            throw new Exception('Private key was not loaded, use setPrivateKeyFromFile or generateKeys methods before');

        if ($password === '')
        {
            $this->private_key = $this->pem_string;
            return true;
        }
        else
        {
            $encrypted_key = openssl_get_privatekey($this->pem_string, $password);
            return openssl_pkey_export($encrypted_key, $this->private_key);
        }

    }


    /**
     * Generates key pair.
     *
     * @param $dest
     * @param $name
     * @param int $bits
     */
    public function generateKeys($dest, $name, $passphrase = null, $bits = 4096)
    {
        // Generate keys
        $keys = openssl_pkey_new([
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);

        // Export private key
        openssl_pkey_export_to_file($keys, $dest . $name . '.pem', empty($passphrase) ? null : $passphrase);
        openssl_pkey_export($keys, $this->pem_string);

        // Export public key
        $details = openssl_pkey_get_details($keys);
        file_put_contents($dest . $name . '.pubkey', $details['key']);
    }


    /**
     * Get exported key
     *
     * @return null
     */
    public function get()
    {
        return $this->private_key;
    }

}