<?php

namespace DevScripts\Password\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class PasswordCheck
{
    public const XML_ENABLED      = 'devscripts_password/general/enabled';
    public const XML_PASSWORD     = 'devscripts_password/general/password';
    public const XML_IP_WHITELIST = 'devscripts_password/general/ip_whitelist';
    public const XML_LOGO         = 'devscripts_password/general/logo';
    public const XML_BACKGROUND   = 'devscripts_password/general/background';

    protected ScopeConfigInterface $scopeConfig;
    protected SessionManagerInterface $session;
    protected ResponseInterface $response;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SessionManagerInterface $session,
        ResponseInterface $response,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->session      = $session;
        $this->response     = $response;
        $this->storeManager = $storeManager;
    }

    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        if (!$this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return;
        }

        $ipList = (string)$this->scopeConfig->getValue(self::XML_IP_WHITELIST, ScopeInterface::SCOPE_STORE);
        if ($ipList) {
            $whitelist = preg_split('/\r\n|\r|\n/', $ipList);
            if ($this->isIpAllowed($this->getClientIp(), $whitelist)) {
                return;
            }
        }

        if ($this->session->getData('website_password_verified')) {
            return;
        }

        if ($request->isPost() && $request->getParam('website_password')) {
            $savedPassword = (string)$this->scopeConfig->getValue(self::XML_PASSWORD, ScopeInterface::SCOPE_STORE);
            if ($savedPassword !== '' &&
                hash_equals($savedPassword, (string)$request->getParam('website_password'))
            ) {
                $this->session->setData('website_password_verified', true);
                return;
            }
        }

        $this->response->setBody($this->getHtml());
        $this->response->sendResponse();
        exit;
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    private function isIpAllowed(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowed) {
            $allowed = trim($allowed);
            if ($allowed === '') {
                continue;
            }

            if (strpos($allowed, '/') !== false && $this->ipInRange($ip, $allowed)) {
                return true;
            }

            if ($ip === $allowed) {
                return true;
            }
        }
        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - (int)$mask)) - 1)) === ip2long($subnet);
    }

    private function getMediaUrl(): string
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    private function getLogoUrl(): string
    {
        $logo = (string)$this->scopeConfig->getValue(
            self::XML_LOGO,
            ScopeInterface::SCOPE_STORE
        );

        if (!$logo) {
            return '';
        }

        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'password_page/' . ltrim($logo, '/');
    }

    private function getBackgroundUrl(): string
    {
        $bg = (string)$this->scopeConfig->getValue(
            self::XML_BACKGROUND,
            ScopeInterface::SCOPE_STORE
        );

        if (!$bg) {
            return '';
        }

        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'password_page/' . ltrim($bg, '/');
    }

    private function getHtml(): string
    {
        $logoUrl = $this->getLogoUrl();
        $bgUrl   = $this->getBackgroundUrl();

        $logoHtml = $logoUrl
            ? '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES) . '" class="logo" alt="Website Logo" />'
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Protected Website</title>
            <style>
                body {
                    margin: 0;
                    font-family: Arial, Helvetica, sans-serif;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f4f6f8 url('{$bgUrl}') center / cover no-repeat;
                }
                .password-wrapper {
                    background: rgba(255,255,255,0.95);
                    max-width: 420px;
                    width: 100%;
                    padding: 40px;
                    border-radius: 8px;
                    text-align: center;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                }
                .password-wrapper img.logo {
                    max-width: 250px;
                    margin-bottom: 20px;
                }
                .password-wrapper h2 {
                    margin-bottom: 10px;
                    color: #333;
                }
                .password-wrapper p {
                    margin-bottom: 25px;
                    color: #666;
                }
                .password-wrapper input {
                    width: 100%;
                    padding: 12px;
                    margin-bottom: 20px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                }
                .password-wrapper button {
                    width: 100%;
                    padding: 12px;
                    background: #007bdb;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                }
                .password-wrapper button:hover {
                    background: #005fa3;
                }
            </style>
        </head>
        <body>
            <div class="password-wrapper">
                {$logoHtml}
                <h2>Protected Website</h2>
                <p>Please enter the password to access this website.</p>
                <form method="post">
                    <input type="password" name="website_password" placeholder="Enter password" required />
                    <button type="submit">Access Website</button>
                </form>
            </div>
        </body>
        </html>
        HTML;
    }
}