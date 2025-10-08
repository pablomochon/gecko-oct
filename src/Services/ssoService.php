<?php

namespace App\Services;

use OneLogin\Saml2\Auth;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class ssoService extends Auth
{
    CONST CONFIG_FILE = '/config/sso.yaml';

    var $_settings = [];

    var $basePath = '';

    public function __construct(KernelInterface $appKernel, $SSO_URL, $SSO_ENTITY_ID, $SSO_CERT_X509, $SSO_CERT_PRIVATE_KEY )
    {
        $this->basePath = $appKernel->getProjectDir();
        $this->_settings = $this->parseConfig( $SSO_URL, $SSO_ENTITY_ID, $SSO_CERT_X509, $SSO_CERT_PRIVATE_KEY );

        parent::__construct($this->_settings['settings']);
    }

    private function parseConfig($SSO_URL, $SSO_ENTITY_ID, $SSO_CERT_X509, $SSO_CERT_PRIVATE_KEY) {
        
        $yaml = file_get_contents($this->basePath . self::CONFIG_FILE);
        $yaml = str_replace('{{SSO_URL}}', $SSO_URL, $yaml );
        $yaml = str_replace('{{SSO_ENTITY_ID}}', $SSO_ENTITY_ID, $yaml );

        $settings = Yaml::parse( $yaml );
        
        $settings['settings']['sp']['x509cert'] = is_file($SSO_CERT_X509) ? file_get_contents($SSO_CERT_X509) : file_get_contents($this->basePath . $SSO_CERT_X509);
        $settings['settings']['sp']['privateKey'] = is_file($SSO_CERT_PRIVATE_KEY) ? file_get_contents($SSO_CERT_PRIVATE_KEY) : file_get_contents($this->basePath . $SSO_CERT_PRIVATE_KEY);
        $settings['settings']['idp']['x509cert'] = $settings['settings']['sp']['x509cert'];
        //dump('sett', $settings);
        return $settings;
    }
}