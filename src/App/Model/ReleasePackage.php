<?php

class Model_ReleasePackage
{

    const RELEASE_API = 'https://api.github.com/repos/mamuph/base/releases/{{version}}';

    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1';

    protected $endpoint;


    /**
     * Model_ReleasePackage constructor.
     *
     * @param string $version
     */
    public function __construct($version = 'latest')
    {
        $this->endpoint = str_replace('{{version}}', $version, self::RELEASE_API);
    }


    /**
     * Get the release information
     *
     * @return bool|mixed
     */
    public function getRelease()
    {
        if (!($release_info = $this->request($this->endpoint)))
            return false;

        return json_decode($release_info);
    }


    /**
     * Get binary package URL
     *
     * @return bool|string
     */
    public function getBinaryURL()
    {
        if (!($release_info = $this->getRelease()))
            return false;

        if (empty($release_info->assets[0]->browser_download_url))
            return false;

        return $release_info->assets[0]->browser_download_url;
    }


    /**
     * Perform a GET request
     *
     * @param $url
     * @return bool|string
     */
    protected function request($url)
    {
        $options  = array('http' => array('user_agent'=> self::USER_AGENT));
        $context  = stream_context_create($options);

        if (!($request_data = @file_get_contents($url, false, $context)))
            return false;

        return $request_data;
    }

}