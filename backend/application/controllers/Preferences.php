<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Preferences extends CI_Controller
{
    public function set_language($language = 'en')
    {
        $language = strtolower($language);

        if (array_key_exists($language, supported_languages())) {
            $this->session->set_userdata('site_language', $language);
        }

        redirect($this->resolveRedirect());
    }

    public function set_currency($currency = 'INR')
    {
        $currency = strtoupper($currency);

        if (array_key_exists($currency, supported_currencies())) {
            $this->session->set_userdata('site_currency', $currency);
        }

        redirect($this->resolveRedirect());
    }

    private function resolveRedirect()
    {
        $fallback = base_url();
        $redirect = $this->input->server('HTTP_REFERER', true);

        if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL)) {
            return $redirect;
        }

        return $fallback;
    }
}
