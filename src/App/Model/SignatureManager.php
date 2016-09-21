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
     * Model_SignatureManager constructor.
     *
     * @param $private_key_file
     * @throws Exception
     */
    public function __construct($private_key_file)
    {

        if (!File::exists($private_key_file, File::SCOPE_EXTERNAL))
            throw new Exception("Private key file not found");


        $this->pem_string = file_get_contents($private_key_file);
    }


    /**
     * Check if private key requires password
     *
     * @return bool
     */
    public function is_protected()
    {
        return stripos($this->pem_string, 'Proc-Type: 4,ENCRYPTED');
    }


    /**
     * Export key
     *
     * @param string $password
     * @return bool
     */
    public function export($password = '')
    {


        if ($this->is_protected())
        {
            $encrypted_key = openssl_get_privatekey($this->pem_string, $password);
            return openssl_pkey_export($encrypted_key, $this->private_key);
        }

        else
        {
            $this->private_key = $this->pem_string;
            return true;
        }
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