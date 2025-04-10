<?php


$this->eu_countries = array(
	"BE",
	"BG",
	"CY",
	"DK",
	"DE",
	"EE",
	"FI",
	"FR",
	"GR",
	"HU",
	"IE",
	"IT",
	"IS",
	"HR",
	"LV",
	"LT",
	"LI",
	"LU",
	"MT",
	"NL",
	"NO",
	"AT",
	"PL",
	"PT",
	"RO",
	"SK",
	"SI",
	"ES",
	"CZ",
	"VL",
	"SE",
	// Extended to Switzerland
	"CH",
	// Extended to Reunion etc
	"RE",
	"GP",
	"MQ",
	"GY",
	"YT",
	"MF",
);

$this->formal_languages = array(
	'de_DE',
	'nl_NL',
);
/**
 * Dataleak type 1: EU, UK
 * Dataleak type 2: US
 * Dataleak type 3: CA, AU, ZA
 *
 */
$this->regions = array(
	'us' => array(
		'label'     => __( 'US', 'complianz-gdpr' ),
		'label_full'=> __( 'United States', 'complianz-gdpr' ),
		'countries' => array( 'US' ),
		'law'       => "CCPA",
		'type'      => 'optout',
		'statistics_consent' => 'no',
		'dataleak_type' => '2',
		'tcf' => true,
	),
	'ca' => array(
		'label'     => __( 'CA', 'complianz-gdpr' ),
		'label_full'=> __( 'Canada', 'complianz-gdpr' ),
		'countries' => array( 'CA' ),
		'law'       => "PIPEDA",
		'type'      => 'optout',
		'statistics_consent' => 'no',
		'dataleak_type' => '3',
		'tcf' => true,
	),
	'eu' => array(
		'label'     => __( 'EU', 'complianz-gdpr' ),
		'label_full'=> __( 'European Union', 'complianz-gdpr' ),
		'countries' => $this->eu_countries,
		'law'       => __( "GDPR", 'complianz-gdpr' ),
		'type'      => 'optin',
		'statistics_consent' => 'when_not_anonymous',
		'dataleak_type' => '1',
		'tcf' => true,
	),
	'uk' => array(
		'label'     => __( 'UK', 'complianz-gdpr' ),
		'label_full'=> __( 'United Kingdom', 'complianz-gdpr' ),
		'countries' => array( 'GB' ),
		'law'       => __( "UK-GDPR", 'complianz-gdpr' ),
		'type'      => 'optin',
		'statistics_consent' => 'always',
		'dataleak_type' => '1',
		'tcf' => true,
	),
	'au' => array(
		'label'     => __( 'AU', 'complianz-gdpr' ),
		'label_full'=> __( 'Australia', 'complianz-gdpr' ),
		'countries' => array( 'AU' ),
		'law'       => "APA",
		'type'      => 'optout',
		'statistics_consent' => 'no',
		'dataleak_type' => '3',
		'tcf' => true,
	),
	'za' => array(
		'label'     => __( 'ZA', 'complianz-gdpr' ),
		'label_full'=> __( 'South Africa', 'complianz-gdpr' ),
		'countries' => array( 'ZA' ),
		'law'       => __( "POPIA", 'complianz-gdpr' ),
		'type'      => 'optin',
		'statistics_consent' => 'no',
		'dataleak_type' => '3',
		'tcf' => true,
	),
	'br' => array(
		'label'     => __( 'BR', 'complianz-gdpr' ),
		'label_full'=> __( 'Brazil', 'complianz-gdpr' ),
		'countries' => array( 'BR' ),
		'law'       => __( "LGPD", 'complianz-gdpr' ),
		'type'      => 'optin',
		'statistics_consent' => 'no',
		'dataleak_type' => '3',
		'tcf' => true,
	)
);

$this->supported_regions = array(
	'eu' => __( 'European Union (GDPR)', 'complianz-gdpr' ),
	'uk' => __( 'United Kingdom (UK-GDPR, PECR, Data Protection Act)', 'complianz-gdpr' ),
	'us' => __( 'United States', 'complianz-gdpr' ),
	'ca' => __( 'Canada (PIPEDA)', 'complianz-gdpr' ),
	'au' => __( 'Australia (Privacy Act 1988)', 'complianz-gdpr' ),
	'za' => __( 'South Africa (POPIA)', 'complianz-gdpr' ),
	'br' => __( 'Brazil (LGPD)', 'complianz-gdpr' ),
);

$this->supported_states = array(
	'cal' => __( 'California (CPRA)', 'complianz-gdpr' ),
	'col' => __( 'Colorado (CPA)', 'complianz-gdpr' ),
	'con' => __( 'Connecticut (CTDPA)', 'complianz-gdpr' ),
	'del' => __( 'Delaware (PDPA)', 'complianz-gdpr' ),
	'iow' => __( 'Iowa (CDPA)', 'complianz-gdpr' ),
	'mon' => __( 'Montana (MCDPA)', 'complianz-gdpr' ),
	'neb' => __( 'Nebraska (DPA)', 'complianz-gdpr' ),
	'nev' => __( 'Nevada (NRS 603A)', 'complianz-gdpr' ),
	'new_ham' => __( 'New Hampshire (DPA)', 'complianz-gdpr' ),
	'new_jer' => __( 'New Jersey (DPL)', 'complianz-gdpr' ),
	'ore' => __( 'Oregon (OCPA)', 'complianz-gdpr' ),
	'tex' => __( 'Texas (TDPSA)', 'complianz-gdpr' ),
	'uta' => __( 'Utah (UCPA)', 'complianz-gdpr' ),
	'vir' => __( 'Virginia (CDPA)', 'complianz-gdpr' ),
);

$this->cookie_consent_converter = array(
	"GB" => "UK",
);

$this->countries = array
(
	'AF' => __( 'Afghanistan' , 'complianz-gdpr' ),
	'AX' => __( 'Aland Islands' , 'complianz-gdpr' ),
	'AL' => __( 'Albania' , 'complianz-gdpr' ),
	'DZ' => __( 'Algeria' , 'complianz-gdpr' ),
	'AS' => __( 'American Samoa' , 'complianz-gdpr' ),
	'AD' => __( 'Andorra' , 'complianz-gdpr' ),
	'AO' => __( 'Angola' , 'complianz-gdpr' ),
	'AI' => __( 'Anguilla' , 'complianz-gdpr' ),
	'AQ' => __( 'Antarctica' , 'complianz-gdpr' ),
	'AG' => __( 'Antigua And Barbuda' , 'complianz-gdpr' ),
	'AR' => __( 'Argentina' , 'complianz-gdpr' ),
	'AM' => __( 'Armenia' , 'complianz-gdpr' ),
	'AW' => __( 'Aruba' , 'complianz-gdpr' ),
	'AU' => __( 'Australia' , 'complianz-gdpr' ),
	'AT' => __( 'Austria' , 'complianz-gdpr' ),
	'AZ' => __( 'Azerbaijan' , 'complianz-gdpr' ),
	'BS' => __( 'Bahamas' , 'complianz-gdpr' ),
	'BH' => __( 'Bahrain' , 'complianz-gdpr' ),
	'BD' => __( 'Bangladesh' , 'complianz-gdpr' ),
	'BB' => __( 'Barbados' , 'complianz-gdpr' ),
	'BY' => __( 'Belarus' , 'complianz-gdpr' ),
	'BE' => __( 'Belgium' , 'complianz-gdpr' ),
	'BZ' => __( 'Belize' , 'complianz-gdpr' ),
	'BJ' => __( 'Benin' , 'complianz-gdpr' ),
	'BM' => __( 'Bermuda' , 'complianz-gdpr' ),
	'BT' => __( 'Bhutan' , 'complianz-gdpr' ),
	'BO' => __( 'Bolivia' , 'complianz-gdpr' ),
	'BA' => __( 'Bosnia And Herzegovina' , 'complianz-gdpr' ),
	'BW' => __( 'Botswana' , 'complianz-gdpr' ),
	'BV' => __( 'Bouvet Island' , 'complianz-gdpr' ),
	'BR' => __( 'Brazil' , 'complianz-gdpr' ),
	'IO' => __( 'British Indian Ocean Territory' , 'complianz-gdpr' ),
	'BN' => __( 'Brunei Darussalam' , 'complianz-gdpr' ),
	'BG' => __( 'Bulgaria' , 'complianz-gdpr' ),
	'BF' => __( 'Burkina Faso' , 'complianz-gdpr' ),
	'BI' => __( 'Burundi' , 'complianz-gdpr' ),
	'KH' => __( 'Cambodia' , 'complianz-gdpr' ),
	'CM' => __( 'Cameroon' , 'complianz-gdpr' ),
	'CA' => __( 'Canada' , 'complianz-gdpr' ),
	'CV' => __( 'Cape Verde' , 'complianz-gdpr' ),
	'KY' => __( 'Cayman Islands' , 'complianz-gdpr' ),
	'CF' => __( 'Central African Republic' , 'complianz-gdpr' ),
	'TD' => __( 'Chad' , 'complianz-gdpr' ),
	'CL' => __( 'Chile' , 'complianz-gdpr' ),
	'CN' => __( 'China' , 'complianz-gdpr' ),
	'CX' => __( 'Christmas Island' , 'complianz-gdpr' ),
	'CC' => __( 'Cocos (Keeling) Islands' , 'complianz-gdpr' ),
	'CO' => __( 'Colombia' , 'complianz-gdpr' ),
	'KM' => __( 'Comoros' , 'complianz-gdpr' ),
	'CG' => __( 'Congo' , 'complianz-gdpr' ),
	'CD' => __( 'Congo, Democratic Republic' , 'complianz-gdpr' ),
	'CK' => __( 'Cook Islands' , 'complianz-gdpr' ),
	'CR' => __( 'Costa Rica' , 'complianz-gdpr' ),
	'CI' => __( 'Cote D\'Ivoire' , 'complianz-gdpr' ),
	'HR' => __( 'Croatia' , 'complianz-gdpr' ),
	'CU' => __( 'Cuba' , 'complianz-gdpr' ),
	'CY' => __( 'Cyprus' , 'complianz-gdpr' ),
	'CZ' => __( 'Czech Republic' , 'complianz-gdpr' ),
	'DK' => __( 'Denmark' , 'complianz-gdpr' ),
	'DJ' => __( 'Djibouti' , 'complianz-gdpr' ),
	'DM' => __( 'Dominica' , 'complianz-gdpr' ),
	'DO' => __( 'Dominican Republic' , 'complianz-gdpr' ),
	'EC' => __( 'Ecuador' , 'complianz-gdpr' ),
	'EG' => __( 'Egypt' , 'complianz-gdpr' ),
	'SV' => __( 'El Salvador' , 'complianz-gdpr' ),
	'GQ' => __( 'Equatorial Guinea' , 'complianz-gdpr' ),
	'ER' => __( 'Eritrea' , 'complianz-gdpr' ),
	'EE' => __( 'Estonia' , 'complianz-gdpr' ),
	'ET' => __( 'Ethiopia' , 'complianz-gdpr' ),
	'FK' => __( 'Falkland Islands (Malvinas)' , 'complianz-gdpr' ),
	'FO' => __( 'Faroe Islands' , 'complianz-gdpr' ),
	'FJ' => __( 'Fiji' , 'complianz-gdpr' ),
	'FI' => __( 'Finland' , 'complianz-gdpr' ),
	'FR' => __( 'France' , 'complianz-gdpr' ),
	'GF' => __( 'French Guiana' , 'complianz-gdpr' ),
	'PF' => __( 'French Polynesia' , 'complianz-gdpr' ),
	'TF' => __( 'French Southern Territories' , 'complianz-gdpr' ),
	'GA' => __( 'Gabon' , 'complianz-gdpr' ),
	'GM' => __( 'Gambia' , 'complianz-gdpr' ),
	'GE' => __( 'Georgia' , 'complianz-gdpr' ),
	'DE' => __( 'Germany' , 'complianz-gdpr' ),
	'GH' => __( 'Ghana' , 'complianz-gdpr' ),
	'GI' => __( 'Gibraltar' , 'complianz-gdpr' ),
	'GR' => __( 'Greece' , 'complianz-gdpr' ),
	'GL' => __( 'Greenland' , 'complianz-gdpr' ),
	'GD' => __( 'Grenada' , 'complianz-gdpr' ),
	'GP' => __( 'Guadeloupe' , 'complianz-gdpr' ),
	'GU' => __( 'Guam' , 'complianz-gdpr' ),
	'GT' => __( 'Guatemala' , 'complianz-gdpr' ),
	'GG' => __( 'Guernsey' , 'complianz-gdpr' ),
	'GN' => __( 'Guinea' , 'complianz-gdpr' ),
	'GW' => __( 'Guinea-Bissau' , 'complianz-gdpr' ),
	'GY' => __( 'Guyana' , 'complianz-gdpr' ),
	'HT' => __( 'Haiti' , 'complianz-gdpr' ),
	'HM' => __( 'Heard Island & Mcdonald Islands' , 'complianz-gdpr' ),
	'VA' => __( 'Holy See (Vatican City State)' , 'complianz-gdpr' ),
	'HN' => __( 'Honduras' , 'complianz-gdpr' ),
	'HK' => __( 'Hong Kong' , 'complianz-gdpr' ),
	'HU' => __( 'Hungary' , 'complianz-gdpr' ),
	'IS' => __( 'Iceland' , 'complianz-gdpr' ),
	'IN' => __( 'India' , 'complianz-gdpr' ),
	'ID' => __( 'Indonesia' , 'complianz-gdpr' ),
	'IR' => __( 'Iran, Islamic Republic Of' , 'complianz-gdpr' ),
	'IQ' => __( 'Iraq' , 'complianz-gdpr' ),
	'IE' => __( 'Ireland' , 'complianz-gdpr' ),
	'IM' => __( 'Isle Of Man' , 'complianz-gdpr' ),
	'IL' => __( 'Israel' , 'complianz-gdpr' ),
	'IT' => __( 'Italy' , 'complianz-gdpr' ),
	'JM' => __( 'Jamaica' , 'complianz-gdpr' ),
	'JP' => __( 'Japan' , 'complianz-gdpr' ),
	'JE' => __( 'Jersey' , 'complianz-gdpr' ),
	'JO' => __( 'Jordan' , 'complianz-gdpr' ),
	'KZ' => __( 'Kazakhstan' , 'complianz-gdpr' ),
	'KE' => __( 'Kenya' , 'complianz-gdpr' ),
	'KI' => __( 'Kiribati' , 'complianz-gdpr' ),
	'KR' => __( 'Korea' , 'complianz-gdpr' ),
	'KW' => __( 'Kuwait' , 'complianz-gdpr' ),
	'KG' => __( 'Kyrgyzstan' , 'complianz-gdpr' ),
	'LA' => __( 'Lao People\'s Democratic Republic' , 'complianz-gdpr' ),
	'LV' => __( 'Latvia' , 'complianz-gdpr' ),
	'LB' => __( 'Lebanon' , 'complianz-gdpr' ),
	'LS' => __( 'Lesotho' , 'complianz-gdpr' ),
	'LR' => __( 'Liberia' , 'complianz-gdpr' ),
	'LY' => __( 'Libyan Arab Jamahiriya' , 'complianz-gdpr' ),
	'LI' => __( 'Liechtenstein' , 'complianz-gdpr' ),
	'LT' => __( 'Lithuania' , 'complianz-gdpr' ),
	'LU' => __( 'Luxembourg' , 'complianz-gdpr' ),
	'MO' => __( 'Macao' , 'complianz-gdpr' ),
	'MK' => __( 'North Macedonia' , 'complianz-gdpr' ),
	'MG' => __( 'Madagascar' , 'complianz-gdpr' ),
	'MW' => __( 'Malawi' , 'complianz-gdpr' ),
	'MY' => __( 'Malaysia' , 'complianz-gdpr' ),
	'MV' => __( 'Maldives' , 'complianz-gdpr' ),
	'ML' => __( 'Mali' , 'complianz-gdpr' ),
	'MT' => __( 'Malta' , 'complianz-gdpr' ),
	'MH' => __( 'Marshall Islands' , 'complianz-gdpr' ),
	'MQ' => __( 'Martinique' , 'complianz-gdpr' ),
	'MR' => __( 'Mauritania' , 'complianz-gdpr' ),
	'MU' => __( 'Mauritius' , 'complianz-gdpr' ),
	'YT' => __( 'Mayotte' , 'complianz-gdpr' ),
	'MX' => __( 'Mexico' , 'complianz-gdpr' ),
	'FM' => __( 'Micronesia, Federated States Of' , 'complianz-gdpr' ),
	'MD' => __( 'Moldova' , 'complianz-gdpr' ),
	'MC' => __( 'Monaco' , 'complianz-gdpr' ),
	'MN' => __( 'Mongolia' , 'complianz-gdpr' ),
	'ME' => __( 'Montenegro' , 'complianz-gdpr' ),
	'MS' => __( 'Montserrat' , 'complianz-gdpr' ),
	'MA' => __( 'Morocco' , 'complianz-gdpr' ),
	'MZ' => __( 'Mozambique' , 'complianz-gdpr' ),
	'MM' => __( 'Myanmar' , 'complianz-gdpr' ),
	'NA' => __( 'Namibia' , 'complianz-gdpr' ),
	'NR' => __( 'Nauru' , 'complianz-gdpr' ),
	'NP' => __( 'Nepal' , 'complianz-gdpr' ),
	'NL' => __( 'Netherlands' , 'complianz-gdpr' ),
	'AN' => __( 'Netherlands Antilles' , 'complianz-gdpr' ),
	'NC' => __( 'New Caledonia' , 'complianz-gdpr' ),
	'NZ' => __( 'New Zealand' , 'complianz-gdpr' ),
	'NI' => __( 'Nicaragua' , 'complianz-gdpr' ),
	'NE' => __( 'Niger' , 'complianz-gdpr' ),
	'NG' => __( 'Nigeria' , 'complianz-gdpr' ),
	'NU' => __( 'Niue' , 'complianz-gdpr' ),
	'NF' => __( 'Norfolk Island' , 'complianz-gdpr' ),
	'MP' => __( 'Northern Mariana Islands' , 'complianz-gdpr' ),
	'NO' => __( 'Norway' , 'complianz-gdpr' ),
	'OM' => __( 'Oman' , 'complianz-gdpr' ),
	'PK' => __( 'Pakistan' , 'complianz-gdpr' ),
	'PW' => __( 'Palau' , 'complianz-gdpr' ),
	'PS' => __( 'Palestinian Territory, Occupied' , 'complianz-gdpr' ),
	'PA' => __( 'Panama' , 'complianz-gdpr' ),
	'PG' => __( 'Papua New Guinea' , 'complianz-gdpr' ),
	'PY' => __( 'Paraguay' , 'complianz-gdpr' ),
	'PE' => __( 'Peru' , 'complianz-gdpr' ),
	'PH' => __( 'Philippines' , 'complianz-gdpr' ),
	'PN' => __( 'Pitcairn' , 'complianz-gdpr' ),
	'PL' => __( 'Poland' , 'complianz-gdpr' ),
	'PT' => __( 'Portugal' , 'complianz-gdpr' ),
	'PR' => __( 'Puerto Rico' , 'complianz-gdpr' ),
	'QA' => __( 'Qatar' , 'complianz-gdpr' ),
	'RE' => __( 'Reunion' , 'complianz-gdpr' ),
	'RO' => __( 'Romania' , 'complianz-gdpr' ),
	'RU' => __( 'Russian Federation' , 'complianz-gdpr' ),
	'RW' => __( 'Rwanda' , 'complianz-gdpr' ),
	'BL' => __( 'Saint Barthelemy' , 'complianz-gdpr' ),
	'SH' => __( 'Saint Helena' , 'complianz-gdpr' ),
	'KN' => __( 'Saint Kitts And Nevis' , 'complianz-gdpr' ),
	'LC' => __( 'Saint Lucia' , 'complianz-gdpr' ),
	'MF' => __( 'Saint Martin' , 'complianz-gdpr' ),
	'PM' => __( 'Saint Pierre And Miquelon' , 'complianz-gdpr' ),
	'VC' => __( 'Saint Vincent And Grenadines' , 'complianz-gdpr' ),
	'WS' => __( 'Samoa' , 'complianz-gdpr' ),
	'SM' => __( 'San Marino' , 'complianz-gdpr' ),
	'ST' => __( 'Sao Tome And Principe' , 'complianz-gdpr' ),
	'SA' => __( 'Saudi Arabia' , 'complianz-gdpr' ),
	'SN' => __( 'Senegal' , 'complianz-gdpr' ),
	'RS' => __( 'Serbia' , 'complianz-gdpr' ),
	'SC' => __( 'Seychelles' , 'complianz-gdpr' ),
	'SL' => __( 'Sierra Leone' , 'complianz-gdpr' ),
	'SG' => __( 'Singapore' , 'complianz-gdpr' ),
	'SK' => __( 'Slovakia' , 'complianz-gdpr' ),
	'SI' => __( 'Slovenia' , 'complianz-gdpr' ),
	'SB' => __( 'Solomon Islands' , 'complianz-gdpr' ),
	'SO' => __( 'Somalia' , 'complianz-gdpr' ),
	'ZA' => __( 'South Africa' , 'complianz-gdpr' ),
	'GS' => __( 'South Georgia And Sandwich Isl.' , 'complianz-gdpr' ),
	'ES' => __( 'Spain' , 'complianz-gdpr' ),
	'LK' => __( 'Sri Lanka' , 'complianz-gdpr' ),
	'SD' => __( 'Sudan' , 'complianz-gdpr' ),
	'SR' => __( 'Suriname' , 'complianz-gdpr' ),
	'SJ' => __( 'Svalbard And Jan Mayen' , 'complianz-gdpr' ),
	'SZ' => __( 'Swaziland' , 'complianz-gdpr' ),
	'SE' => __( 'Sweden' , 'complianz-gdpr' ),
	'CH' => __( 'Switzerland' , 'complianz-gdpr' ),
	'SY' => __( 'Syrian Arab Republic' , 'complianz-gdpr' ),
	'TW' => __( 'Taiwan' , 'complianz-gdpr' ),
	'TJ' => __( 'Tajikistan' , 'complianz-gdpr' ),
	'TZ' => __( 'Tanzania' , 'complianz-gdpr' ),
	'TH' => __( 'Thailand' , 'complianz-gdpr' ),
	'TL' => __( 'Timor-Leste' , 'complianz-gdpr' ),
	'TG' => __( 'Togo' , 'complianz-gdpr' ),
	'TK' => __( 'Tokelau' , 'complianz-gdpr' ),
	'TO' => __( 'Tonga' , 'complianz-gdpr' ),
	'TT' => __( 'Trinidad And Tobago' , 'complianz-gdpr' ),
	'TN' => __( 'Tunisia' , 'complianz-gdpr' ),
	'TR' => __( 'Turkey' , 'complianz-gdpr' ),
	'TM' => __( 'Turkmenistan' , 'complianz-gdpr' ),
	'TC' => __( 'Turks And Caicos Islands' , 'complianz-gdpr' ),
	'TV' => __( 'Tuvalu' , 'complianz-gdpr' ),
	'UG' => __( 'Uganda' , 'complianz-gdpr' ),
	'UA' => __( 'Ukraine' , 'complianz-gdpr' ),
	'AE' => __( 'United Arab Emirates' , 'complianz-gdpr' ),
	'GB' => __( 'United Kingdom' , 'complianz-gdpr' ),
	'US' => __( 'United States' , 'complianz-gdpr' ),
	'UM' => __( 'United States Outlying Islands' , 'complianz-gdpr' ),
	'UY' => __( 'Uruguay' , 'complianz-gdpr' ),
	'UZ' => __( 'Uzbekistan' , 'complianz-gdpr' ),
	'VU' => __( 'Vanuatu' , 'complianz-gdpr' ),
	'VE' => __( 'Venezuela' , 'complianz-gdpr' ),
	'VN' => __( 'Viet Nam' , 'complianz-gdpr' ),
	'VG' => __( 'Virgin Islands, British' , 'complianz-gdpr' ),
	'VI' => __( 'Virgin Islands, U.S.' , 'complianz-gdpr' ),
	'WF' => __( 'Wallis And Futuna' , 'complianz-gdpr' ),
	'EH' => __( 'Western Sahara' , 'complianz-gdpr' ),
	'YE' => __( 'Yemen' , 'complianz-gdpr' ),
	'ZM' => __( 'Zambia' , 'complianz-gdpr' ),
	'ZW' => __( 'Zimbabwe' , 'complianz-gdpr' ),
);

/**
 * Used in dropdown in cookies editor in wizard. Only major languages to limit translatable strings
 */

$this->language_codes = array(
	'en' => __( 'English', 'complianz-gdpr' ),
	'da' => __( 'Danish', 'complianz-gdpr' ),
	'de' => __( 'German', 'complianz-gdpr' ),
	'el' => __( 'Greek', 'complianz-gdpr' ),
	'es' => __( 'Spanish', 'complianz-gdpr' ),
	'et' => __( 'Estonian', 'complianz-gdpr' ),
	'fr' => __( 'French', 'complianz-gdpr' ),
	'it' => __( 'Italian', 'complianz-gdpr' ),
	'nl' => __( 'Dutch', 'complianz-gdpr' ),
	'no' => __( 'Norwegian', 'complianz-gdpr' ),
	'sv' => __( 'Swedish', 'complianz-gdpr' ),
);
