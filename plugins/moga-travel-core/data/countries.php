<?php
/**
 * Countries Data Library
 *
 * Complete list of world countries with:
 *   - ISO 3166-1 alpha-2 code
 *   - Country name (English)
 *   - International dial code
 *   - Flag emoji
 *   - Default currency code
 *
 * Used by: meta boxes, search forms, intl-tel-input phone field.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/data
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all countries data.
 *
 * @since  1.0.0
 * @return array Array of country data arrays.
 */
function moga_get_countries() {
    return array(

        // ---- Arab Countries (listed first for regional relevance) ----
        array( 'code' => 'EG', 'name' => 'Egypt',                        'dial' => '+20',   'flag' => '🇪🇬', 'currency' => 'EGP' ),
        array( 'code' => 'SA', 'name' => 'Saudi Arabia',                 'dial' => '+966',  'flag' => '🇸🇦', 'currency' => 'SAR' ),
        array( 'code' => 'AE', 'name' => 'United Arab Emirates',         'dial' => '+971',  'flag' => '🇦🇪', 'currency' => 'AED' ),
        array( 'code' => 'KW', 'name' => 'Kuwait',                       'dial' => '+965',  'flag' => '🇰🇼', 'currency' => 'KWD' ),
        array( 'code' => 'QA', 'name' => 'Qatar',                        'dial' => '+974',  'flag' => '🇶🇦', 'currency' => 'QAR' ),
        array( 'code' => 'BH', 'name' => 'Bahrain',                      'dial' => '+973',  'flag' => '🇧🇭', 'currency' => 'BHD' ),
        array( 'code' => 'OM', 'name' => 'Oman',                         'dial' => '+968',  'flag' => '🇴🇲', 'currency' => 'OMR' ),
        array( 'code' => 'JO', 'name' => 'Jordan',                       'dial' => '+962',  'flag' => '🇯🇴', 'currency' => 'JOD' ),
        array( 'code' => 'LB', 'name' => 'Lebanon',                      'dial' => '+961',  'flag' => '🇱🇧', 'currency' => 'LBP' ),
        array( 'code' => 'SY', 'name' => 'Syria',                        'dial' => '+963',  'flag' => '🇸🇾', 'currency' => 'SYP' ),
        array( 'code' => 'IQ', 'name' => 'Iraq',                         'dial' => '+964',  'flag' => '🇮🇶', 'currency' => 'IQD' ),
        array( 'code' => 'YE', 'name' => 'Yemen',                        'dial' => '+967',  'flag' => '🇾🇪', 'currency' => 'YER' ),
        array( 'code' => 'LY', 'name' => 'Libya',                        'dial' => '+218',  'flag' => '🇱🇾', 'currency' => 'LYD' ),
        array( 'code' => 'TN', 'name' => 'Tunisia',                      'dial' => '+216',  'flag' => '🇹🇳', 'currency' => 'TND' ),
        array( 'code' => 'DZ', 'name' => 'Algeria',                      'dial' => '+213',  'flag' => '🇩🇿', 'currency' => 'DZD' ),
        array( 'code' => 'MA', 'name' => 'Morocco',                      'dial' => '+212',  'flag' => '🇲🇦', 'currency' => 'MAD' ),
        array( 'code' => 'SD', 'name' => 'Sudan',                        'dial' => '+249',  'flag' => '🇸🇩', 'currency' => 'SDG' ),
        array( 'code' => 'SO', 'name' => 'Somalia',                      'dial' => '+252',  'flag' => '🇸🇴', 'currency' => 'SOS' ),
        array( 'code' => 'MR', 'name' => 'Mauritania',                   'dial' => '+222',  'flag' => '🇲🇷', 'currency' => 'MRU' ),
        array( 'code' => 'DJ', 'name' => 'Djibouti',                     'dial' => '+253',  'flag' => '🇩🇯', 'currency' => 'DJF' ),
        array( 'code' => 'KM', 'name' => 'Comoros',                      'dial' => '+269',  'flag' => '🇰🇲', 'currency' => 'KMF' ),
        array( 'code' => 'PS', 'name' => 'Palestine',                    'dial' => '+970',  'flag' => '🇵🇸', 'currency' => 'ILS' ),

        // ---- Middle East & Near East ----
        array( 'code' => 'TR', 'name' => 'Turkey',                       'dial' => '+90',   'flag' => '🇹🇷', 'currency' => 'TRY' ),
        array( 'code' => 'IR', 'name' => 'Iran',                         'dial' => '+98',   'flag' => '🇮🇷', 'currency' => 'IRR' ),
        array( 'code' => 'IL', 'name' => 'Israel',                       'dial' => '+972',  'flag' => '🇮🇱', 'currency' => 'ILS' ),
        array( 'code' => 'CY', 'name' => 'Cyprus',                       'dial' => '+357',  'flag' => '🇨🇾', 'currency' => 'EUR' ),

        // ---- Africa ----
        array( 'code' => 'NG', 'name' => 'Nigeria',                      'dial' => '+234',  'flag' => '🇳🇬', 'currency' => 'NGN' ),
        array( 'code' => 'ZA', 'name' => 'South Africa',                 'dial' => '+27',   'flag' => '🇿🇦', 'currency' => 'ZAR' ),
        array( 'code' => 'KE', 'name' => 'Kenya',                        'dial' => '+254',  'flag' => '🇰🇪', 'currency' => 'KES' ),
        array( 'code' => 'ET', 'name' => 'Ethiopia',                     'dial' => '+251',  'flag' => '🇪🇹', 'currency' => 'ETB' ),
        array( 'code' => 'GH', 'name' => 'Ghana',                        'dial' => '+233',  'flag' => '🇬🇭', 'currency' => 'GHS' ),
        array( 'code' => 'TZ', 'name' => 'Tanzania',                     'dial' => '+255',  'flag' => '🇹🇿', 'currency' => 'TZS' ),
        array( 'code' => 'UG', 'name' => 'Uganda',                       'dial' => '+256',  'flag' => '🇺🇬', 'currency' => 'UGX' ),
        array( 'code' => 'CM', 'name' => 'Cameroon',                     'dial' => '+237',  'flag' => '🇨🇲', 'currency' => 'XAF' ),
        array( 'code' => 'CI', 'name' => 'Ivory Coast',                  'dial' => '+225',  'flag' => '🇨🇮', 'currency' => 'XOF' ),
        array( 'code' => 'SN', 'name' => 'Senegal',                      'dial' => '+221',  'flag' => '🇸🇳', 'currency' => 'XOF' ),
        array( 'code' => 'MZ', 'name' => 'Mozambique',                   'dial' => '+258',  'flag' => '🇲🇿', 'currency' => 'MZN' ),
        array( 'code' => 'ZM', 'name' => 'Zambia',                       'dial' => '+260',  'flag' => '🇿🇲', 'currency' => 'ZMW' ),
        array( 'code' => 'ZW', 'name' => 'Zimbabwe',                     'dial' => '+263',  'flag' => '🇿🇼', 'currency' => 'ZWL' ),
        array( 'code' => 'MG', 'name' => 'Madagascar',                   'dial' => '+261',  'flag' => '🇲🇬', 'currency' => 'MGA' ),

        // ---- Europe ----
        array( 'code' => 'GB', 'name' => 'United Kingdom',               'dial' => '+44',   'flag' => '🇬🇧', 'currency' => 'GBP' ),
        array( 'code' => 'DE', 'name' => 'Germany',                      'dial' => '+49',   'flag' => '🇩🇪', 'currency' => 'EUR' ),
        array( 'code' => 'FR', 'name' => 'France',                       'dial' => '+33',   'flag' => '🇫🇷', 'currency' => 'EUR' ),
        array( 'code' => 'IT', 'name' => 'Italy',                        'dial' => '+39',   'flag' => '🇮🇹', 'currency' => 'EUR' ),
        array( 'code' => 'ES', 'name' => 'Spain',                        'dial' => '+34',   'flag' => '🇪🇸', 'currency' => 'EUR' ),
        array( 'code' => 'NL', 'name' => 'Netherlands',                  'dial' => '+31',   'flag' => '🇳🇱', 'currency' => 'EUR' ),
        array( 'code' => 'BE', 'name' => 'Belgium',                      'dial' => '+32',   'flag' => '🇧🇪', 'currency' => 'EUR' ),
        array( 'code' => 'SE', 'name' => 'Sweden',                       'dial' => '+46',   'flag' => '🇸🇪', 'currency' => 'SEK' ),
        array( 'code' => 'NO', 'name' => 'Norway',                       'dial' => '+47',   'flag' => '🇳🇴', 'currency' => 'NOK' ),
        array( 'code' => 'DK', 'name' => 'Denmark',                      'dial' => '+45',   'flag' => '🇩🇰', 'currency' => 'DKK' ),
        array( 'code' => 'FI', 'name' => 'Finland',                      'dial' => '+358',  'flag' => '🇫🇮', 'currency' => 'EUR' ),
        array( 'code' => 'PT', 'name' => 'Portugal',                     'dial' => '+351',  'flag' => '🇵🇹', 'currency' => 'EUR' ),
        array( 'code' => 'GR', 'name' => 'Greece',                       'dial' => '+30',   'flag' => '🇬🇷', 'currency' => 'EUR' ),
        array( 'code' => 'PL', 'name' => 'Poland',                       'dial' => '+48',   'flag' => '🇵🇱', 'currency' => 'PLN' ),
        array( 'code' => 'RU', 'name' => 'Russia',                       'dial' => '+7',    'flag' => '🇷🇺', 'currency' => 'RUB' ),
        array( 'code' => 'UA', 'name' => 'Ukraine',                      'dial' => '+380',  'flag' => '🇺🇦', 'currency' => 'UAH' ),
        array( 'code' => 'CH', 'name' => 'Switzerland',                  'dial' => '+41',   'flag' => '🇨🇭', 'currency' => 'CHF' ),
        array( 'code' => 'AT', 'name' => 'Austria',                      'dial' => '+43',   'flag' => '🇦🇹', 'currency' => 'EUR' ),
        array( 'code' => 'CZ', 'name' => 'Czech Republic',               'dial' => '+420',  'flag' => '🇨🇿', 'currency' => 'CZK' ),
        array( 'code' => 'HU', 'name' => 'Hungary',                      'dial' => '+36',   'flag' => '🇭🇺', 'currency' => 'HUF' ),
        array( 'code' => 'RO', 'name' => 'Romania',                      'dial' => '+40',   'flag' => '🇷🇴', 'currency' => 'RON' ),
        array( 'code' => 'HR', 'name' => 'Croatia',                      'dial' => '+385',  'flag' => '🇭🇷', 'currency' => 'EUR' ),
        array( 'code' => 'SK', 'name' => 'Slovakia',                     'dial' => '+421',  'flag' => '🇸🇰', 'currency' => 'EUR' ),
        array( 'code' => 'BG', 'name' => 'Bulgaria',                     'dial' => '+359',  'flag' => '🇧🇬', 'currency' => 'BGN' ),
        array( 'code' => 'RS', 'name' => 'Serbia',                       'dial' => '+381',  'flag' => '🇷🇸', 'currency' => 'RSD' ),
        array( 'code' => 'IE', 'name' => 'Ireland',                      'dial' => '+353',  'flag' => '🇮🇪', 'currency' => 'EUR' ),

        // ---- Americas ----
        array( 'code' => 'US', 'name' => 'United States',                'dial' => '+1',    'flag' => '🇺🇸', 'currency' => 'USD' ),
        array( 'code' => 'CA', 'name' => 'Canada',                       'dial' => '+1',    'flag' => '🇨🇦', 'currency' => 'CAD' ),
        array( 'code' => 'MX', 'name' => 'Mexico',                       'dial' => '+52',   'flag' => '🇲🇽', 'currency' => 'MXN' ),
        array( 'code' => 'BR', 'name' => 'Brazil',                       'dial' => '+55',   'flag' => '🇧🇷', 'currency' => 'BRL' ),
        array( 'code' => 'AR', 'name' => 'Argentina',                    'dial' => '+54',   'flag' => '🇦🇷', 'currency' => 'ARS' ),
        array( 'code' => 'CO', 'name' => 'Colombia',                     'dial' => '+57',   'flag' => '🇨🇴', 'currency' => 'COP' ),
        array( 'code' => 'CL', 'name' => 'Chile',                        'dial' => '+56',   'flag' => '🇨🇱', 'currency' => 'CLP' ),
        array( 'code' => 'PE', 'name' => 'Peru',                         'dial' => '+51',   'flag' => '🇵🇪', 'currency' => 'PEN' ),

        // ---- Asia ----
        array( 'code' => 'CN', 'name' => 'China',                        'dial' => '+86',   'flag' => '🇨🇳', 'currency' => 'CNY' ),
        array( 'code' => 'JP', 'name' => 'Japan',                        'dial' => '+81',   'flag' => '🇯🇵', 'currency' => 'JPY' ),
        array( 'code' => 'IN', 'name' => 'India',                        'dial' => '+91',   'flag' => '🇮🇳', 'currency' => 'INR' ),
        array( 'code' => 'KR', 'name' => 'South Korea',                  'dial' => '+82',   'flag' => '🇰🇷', 'currency' => 'KRW' ),
        array( 'code' => 'PK', 'name' => 'Pakistan',                     'dial' => '+92',   'flag' => '🇵🇰', 'currency' => 'PKR' ),
        array( 'code' => 'BD', 'name' => 'Bangladesh',                   'dial' => '+880',  'flag' => '🇧🇩', 'currency' => 'BDT' ),
        array( 'code' => 'ID', 'name' => 'Indonesia',                    'dial' => '+62',   'flag' => '🇮🇩', 'currency' => 'IDR' ),
        array( 'code' => 'TH', 'name' => 'Thailand',                     'dial' => '+66',   'flag' => '🇹🇭', 'currency' => 'THB' ),
        array( 'code' => 'VN', 'name' => 'Vietnam',                      'dial' => '+84',   'flag' => '🇻🇳', 'currency' => 'VND' ),
        array( 'code' => 'PH', 'name' => 'Philippines',                  'dial' => '+63',   'flag' => '🇵🇭', 'currency' => 'PHP' ),
        array( 'code' => 'MY', 'name' => 'Malaysia',                     'dial' => '+60',   'flag' => '🇲🇾', 'currency' => 'MYR' ),
        array( 'code' => 'SG', 'name' => 'Singapore',                    'dial' => '+65',   'flag' => '🇸🇬', 'currency' => 'SGD' ),
        array( 'code' => 'MM', 'name' => 'Myanmar',                      'dial' => '+95',   'flag' => '🇲🇲', 'currency' => 'MMK' ),
        array( 'code' => 'AF', 'name' => 'Afghanistan',                  'dial' => '+93',   'flag' => '🇦🇫', 'currency' => 'AFN' ),
        array( 'code' => 'NP', 'name' => 'Nepal',                        'dial' => '+977',  'flag' => '🇳🇵', 'currency' => 'NPR' ),
        array( 'code' => 'LK', 'name' => 'Sri Lanka',                    'dial' => '+94',   'flag' => '🇱🇰', 'currency' => 'LKR' ),
        array( 'code' => 'KZ', 'name' => 'Kazakhstan',                   'dial' => '+7',    'flag' => '🇰🇿', 'currency' => 'KZT' ),
        array( 'code' => 'UZ', 'name' => 'Uzbekistan',                   'dial' => '+998',  'flag' => '🇺🇿', 'currency' => 'UZS' ),
        array( 'code' => 'AZ', 'name' => 'Azerbaijan',                   'dial' => '+994',  'flag' => '🇦🇿', 'currency' => 'AZN' ),
        array( 'code' => 'AM', 'name' => 'Armenia',                      'dial' => '+374',  'flag' => '🇦🇲', 'currency' => 'AMD' ),
        array( 'code' => 'GE', 'name' => 'Georgia',                      'dial' => '+995',  'flag' => '🇬🇪', 'currency' => 'GEL' ),

        // ---- Oceania ----
        array( 'code' => 'AU', 'name' => 'Australia',                    'dial' => '+61',   'flag' => '🇦🇺', 'currency' => 'AUD' ),
        array( 'code' => 'NZ', 'name' => 'New Zealand',                  'dial' => '+64',   'flag' => '🇳🇿', 'currency' => 'NZD' ),
    );
}


/**
 * Get a single country by ISO code.
 *
 * @since  1.0.0
 * @param  string $code ISO 3166-1 alpha-2 country code.
 * @return array|null   Country data array or null if not found.
 */
function moga_get_country( $code ) {
    $code      = strtoupper( $code );
    $countries = moga_get_countries();

    foreach ( $countries as $country ) {
        if ( $country['code'] === $code ) {
            return $country;
        }
    }

    return null;
}


/**
 * Get countries formatted for a select dropdown.
 *
 * @since  1.0.0
 * @param  bool $include_dial Whether to include dial code in label.
 * @return array              code => label pairs.
 */
function moga_get_countries_dropdown( $include_dial = false ) {
    $countries = moga_get_countries();
    $result    = array(
        '' => __( '— Select Country —', 'moga-travel-core' ),
    );

    foreach ( $countries as $country ) {
        $label = $country['flag'] . ' ' . $country['name'];

        if ( $include_dial ) {
            $label .= ' (' . $country['dial'] . ')';
        }

        $result[ $country['code'] ] = $label;
    }

    return $result;
}


/**
 * Get countries formatted for intl-tel-input JavaScript library.
 *
 * Returns JSON-ready array for initializing the phone flag picker.
 *
 * @since  1.0.0
 * @return array Array of country data for intl-tel-input.
 */
function moga_get_countries_for_phone() {
    $countries = moga_get_countries();
    $result    = array();

    foreach ( $countries as $country ) {
        $result[] = array(
            'name' => $country['name'],
            'iso2' => strtolower( $country['code'] ),
            'dial' => ltrim( $country['dial'], '+' ),
        );
    }

    return $result;
}


/**
 * Get dial code for a country ISO code.
 *
 * @since  1.0.0
 * @param  string $code ISO country code (e.g. 'EG').
 * @return string       Dial code with + prefix (e.g. '+20') or empty string.
 */
function moga_get_dial_code( $code ) {
    $country = moga_get_country( $code );
    return $country ? $country['dial'] : '';
}


/**
 * Get flag emoji for a country ISO code.
 *
 * @since  1.0.0
 * @param  string $code ISO country code (e.g. 'EG').
 * @return string       Flag emoji (e.g. '🇪🇬') or empty string.
 */
function moga_get_country_flag( $code ) {
    $country = moga_get_country( $code );
    return $country ? $country['flag'] : '';
}