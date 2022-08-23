<?php

function moy_get_site_domain()
{
    if (preg_match('/https?:\/\/([\w\d\.\-]+)\/?.*/', get_site_url(), $matches) === false) {
        return null;
    }

    return isset($matches[1]) ? $matches[1] : null;
}

function moy_trimmed_site_url()
{
    return remove_url_fragment(rtrim(get_site_url(null, '/'), '/'));
}

function moy_payment_apple_pay_validate_merchant_url()
{
    return remove_url_fragment(get_site_url(null, '/?rest_route=/moyasar/v2/apple-pay/validate-merchant'));
}

function moyasar_page_url($page)
{
    return remove_url_fragment(get_site_url(null, "/?moyasar_page=$page"));
}

function remove_url_fragment($url)
{
    // Remove any URL fragments
    return preg_replace('/#[^&]+/', '', urldecode($url));
}
