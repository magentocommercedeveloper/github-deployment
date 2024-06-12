<?php
namespace Ssquare\Github\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_PATH_GITHUB_REPO_URL = 'ssquare_github/general/repo_url';
    const XML_PATH_GITHUB_TOKEN = 'ssquare_github/general/token';

    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getGithubRepoUrl()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GITHUB_REPO_URL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getGithubToken()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_GITHUB_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
