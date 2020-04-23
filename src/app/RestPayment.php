<?php

declare(strict_types=1);

namespace Paytrail\Rest;

use Paytrail\Exceptions\TemplateException;

class RestPayment
{
    const TEMPLATE_PATH = '/../templates/';

    protected $orderNumber;
    protected $paymentData;
    private $customer;
    private $products;
    private $price;

    public function __construct(string $orderNumber, array $paymentData, ?Customer $customer = null, ?array $products = null, ?float $price = null)
    {
        $this->orderNumber = $orderNumber;
        $this->customer = $customer;
        $this->products = $products;
        $this->paymentData = $paymentData;
        $this->price = $price;
    }

    public function getJsonData(): string
    {
        $data = [
            'orderNumber' => $this->orderNumber,
            'description' => $this->paymentData['description'] ?? '',
            'currency' => $this->paymentData['currency'] ?? 'EUR',
            'locale' => $this->paymentData['locale'] ?? 'fi_FI',
            'urlSet' => [
                'success' => $this->paymentData['urlSet']['success'] ?? $this->getServerUrl() . 'success',
                'failure' => $this->paymentData['urlSet']['failure'] ?? $this->getServerUrl() . 'failure',
                'pending' => '',
                'notification' => $this->paymentData['urlSet']['notification'] ?? $this->getServerUrl() . 'notify',
            ],
        ];

        if ($this->price) {
            $data['price'] = $this->price;

            return json_encode($data);
        }

        $data['orderDetails'] = [
            'includeVat' => $this->paymentData['orderDetails']['includeVat'] ?? '1',
            'contact' => [],
            'products' => [],
        ];

        if ($this->customer) {
            $data['orderDetails']['contact'] = [
                'telephone' => $this->customer->telephone,
                'mobile' => $this->customer->mobile,
                'email' => $this->customer->email,
                'firstName' => $this->customer->firstName,
                'lastName' => $this->customer->lastName,
                'companyName' => $this->customer->companyName,
                'address' => [
                    'street' => $this->customer->street,
                    'postalCode' => $this->customer->postalCode,
                    'postalOffice' => $this->customer->postalOffice,
                    'country' => $this->customer->country,
                ]
            ];
        }

        foreach ($this->products as $product) {
            $data['orderDetails']['products'][] = [
                'title' => $product->title,
                'code' => $product->code,
                'amount' => $product->amount,
                'price' => $product->price,
                'vat' => $product->vat,
                'discount' => $product->discount,
                'type' => $product->type
            ];
        }

        return json_encode($data);
    }

    public function getXmlData(): string
    {
        $data = [
            'orderNumber' => $this->orderNumber,
            'paymentData' => $this->paymentData,
            'successUrl' => $this->paymentData['urlSet']['success'] ?? $this->getServerUrl() . 'success',
            'failureUrl' => $this->paymentData['urlSet']['failure'] ?? $this->getServerUrl() . 'failure',
            'notificationUrl' => $this->paymentData['urlSet']['notification'] ?? $this->getServerUrl() . 'notify',
        ];

        if ($this->price) {
            $data['price'] = $this->price;
        }

        if ($this->customer) {
            $data['customer'] = $this->customer;
        }

        if ($this->products) {
            $data['products'] = $this->products;
        }

        return $this->getXmlTemplate('xml', $data);
    }

    private function getServerUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return "{$protocol}://{$host}{$requestUri}";
    }

    private function getXmlTemplate(string $templateName, array $data = []): string
    {
        $templateFile = __DIR__ . self::TEMPLATE_PATH . basename($templateName) . '.phtml';

        if (!file_exists($templateFile)) {
            throw new TemplateException("Template for {$templateName} not found");
        }

        foreach ($data as $key => $value) {
            $$key = $value;
        }

        ob_start();
        include $templateFile;
        $xml = ob_get_contents();
        ob_end_clean();

        return $xml;
    }
}
