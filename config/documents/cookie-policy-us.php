<?php
/*
 * This document is intentionally not translatable, as it is intended to be for US citizens, and should therefore always be in English
 *
 *
 */

defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['us']['cookie-statement']['document_elements'] = array(
    array(
        'content' => '<i>' . cmplz_sprintf("This page was last changed on %s, last checked on %s and applies to citizens and legal permanent residents of the United States. ", '[publish_date]', '[checked_date]') . '</i><br />',
    ),
    array(
        'title' => 'Introduction',
        'content' => cmplz_sprintf('Our website, %s (hereinafter: "the website") uses cookies and other related technologies (for convenience all technologies are referred to as "cookies"). Cookies are also placed by third parties we have engaged. In the document below we inform you about the use of cookies on our website.', '[domain]'),
    ),

	array(
		'title' => 'Selling data to third parties',
		'callback_condition' => 'cmplz_sells_personal_data',
		'condition' => array(
			'us_states' => 'NOT EMPTY',
		),
	),

	array(
		'content' => "Our privacy statement describes the limited circumstances under which we share or sell personal information to third parties and if we use or disclose sensitive personal information. You may request that we exclude your personal information from such arrangements, or direct us to limit the use and disclosure of possible sensitive personal information, by entering your name and email address below. You may need to provide additional identifying information before we can process your request.",
		'callback_condition' => 'cmplz_sells_personal_data',
		'condition' => array(
			'us_states' => 'NOT EMPTY',
		),
	),

	array(
		'subtitle' => 'Categories of data',
		'content' => 'The following categories of data are sold to third parties',
		'callback_condition' => 'cmplz_sells_personal_data',
		'condition' => array(
			'us_states' => 'NOT EMPTY',
		),
	),

	array(
		'content' => '[selling-data-thirdparty_data_purpose_us]',
		'callback_condition' => 'cmplz_sells_personal_data',
		'condition' => array(
			'us_states' => 'NOT EMPTY',
		),
	),

  array(
      'content' => "We do not sell or share personal information to third parties for monetary consideration; however, we may disclose certain personal information to third parties under circumstances that might be deemed a “sale” or ”Sharing” for residents of [comma_us_states].
We respect and understand that you may want to be sure that your personal information is not being sold or shared. You may request that we exclude your personal information from such arrangements, or direct us to limit the use and disclosure of possible sensitive personal information, by entering your name and email address below. You may need to provide additional identifying information before we can process your request.",
      'callback_condition' => 'NOT cmplz_sells_personal_data',
      'condition' => array(
          'us_states' => 'NOT EMPTY',
      ),
  ),

	array(
		'content' => '[cmplz-dnsmpi-request]',
		'condition' => array(
			'us_states' => 'NOT EMPTY',
		),
	),

    array(
        'title' => 'Cookies',
        'content' => 'When you visit our website it can be necessary to store and/or read certain data from your device by using technologies such as cookies.',
    ),
    array(
        'subtitle' => 'Technical or functional cookies',
        'content' => 'Some cookies ensure that certain parts of the website work properly and that your user preferences remain known. By placing functional cookies, we make it easier for you to visit our website. This way, you do not need to repeatedly enter the same information when visiting our website and, for example, the items remain in your shopping cart until you have paid. We may place these cookies without your consent.',
    ),

    array(
        'subtitle' => 'Statistics cookies',
        'content' => 'We use statistics cookies to optimize the website experience for our users. With these statistics cookies we get insights in the usage of our website.',
        'callback_condition' => 'cmplz_uses_statistics',
        'condition' => array('compile_statistics' => 'NOT no'),
    ),

    //ads
    array(
        'subtitle' => 'Advertising cookies',
        'content' => cmplz_sprintf('On this website we use advertising cookies, enabling us to personalize the advertisements for you, and we (and third parties) gain insights into the campaign results. This happens based on a profile we create based on your click and surfing on and outside %s. With these cookies you, as website visitor are linked to a unique ID, so you do not see the same ad more than once for example.', '[domain]'),
        'condition' => array(
            'uses_ad_cookies' => 'yes',
            'uses_ad_cookies_personalized' => 'NOT no'
        ),
    ),

    array(
        'subtitle' => 'Advertising cookies',
        'content' => cmplz_sprintf('On this website we use advertising cookies, enabling us to gain insights into the campaign results. This happens based on a profile we create based on your behavior on %s. With these cookies you, as website visitor, are linked to a unique ID but these cookies will not profile your behavior and interests to serve personalized ads.', '[domain]'),
        'condition' => array(
            'uses_ad_cookies' => 'yes',
            'uses_ad_cookies_personalized' => 'no'
        ),
    ),

    array(
        'content' => 'You can object to the tracking by these cookies by clicking the "Manage Consent" button.',
        'condition' => array(
            'uses_ad_cookies' => 'yes',
        ),
    ),

	array(
		'subtitle' => 'Marketing/Tracking cookies',
		'content' => 'Marketing/Tracking cookies are cookies or any other form of local storage, used to create user profiles to display advertising or to track the user on this website or across several websites for similar marketing purposes.',
//		'condition' => array(
//			'uses_ad_cookies' => 'no',
//		),
		'callback_condition' => 'cmplz_uses_marketing_cookies',
	),

	array(
		'subtitle' => _x('Social media', 'Legal document cookie policy:paragraph title', 'complianz-gdpr'),
		'content' => _x('On our website, we have included content to promote web pages (e.g. “like”, “pin”) or share (e.g. “tweet”) on social networks. This content is embedded with code derived from third parties and places cookies. This content might store and process certain information for personalized advertising.', 'Legal document cookie policy', 'complianz-gdpr'),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'EMPTY',
		),
	),
	array(
		'subtitle' => 'Social media',
		'content' => cmplz_sprintf(__('On our website, we have included content from %s to promote web pages (e.g. “like”, “pin”) or share (e.g. “tweet”) on social networks like %s. This content is embedded with code derived from %s and places cookies. This content might store and process certain information for personalized advertising.', 'complianz-gdpr'), '[comma_socialmedia_on_site]', '[comma_socialmedia_on_site]', '[comma_socialmedia_on_site]'),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'NOT EMPTY',
		),
	),

	array(
		'content' => __('Please read the privacy statement of these social networks (which can change regularly) to read what they do with your (personal) data which they process using these cookies. The data that is retrieved is anonymized as much as possible.','complianz-gdpr').' '.cmplz_sprintf( _n( '%s is located in the United States.', '%s are located in the United States.',  cmplz_count_socialmedia(), 'complianz-gdpr'  ) ,'[comma_socialmedia_on_site]' ),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'NOT EMPTY',
		),
	),

	array(
		'content' => __('Please read the privacy statement of these social networks (which can change regularly) to read what they do with your (personal) data which they process using these cookies. The data that is retrieved is anonymized as much as possible.','complianz-gdpr'),
		'condition' => array(
			'uses_social_media' => 'yes',
			'socialmedia_on_site' => 'EMPTY',
		),
	),

	  'cookie_names' => array(
		'title' => 'Placed cookies',
    'content' =>
                '<p>Most of these technologies have a function, a purpose, and an expiration period.</p>' .
                '<ol class="alphabetic>
                      <li>A function is a particular task a technology has. So a function can be to "store certain data."</li>
                      <li>Purpose is "the Why" behind the function. Maybe the data is stored because it is needed for statistics.</li>
                      <li>The expiration period shows the length of the period the used technology can “store or read certain data."</li>
                  </ol>',

		'callback' => 'cmplz_used_cookies',
	),

  array(
      'title' => 'Browser and Device based Consent',
      'content' => 'When you visit our website for the first time, we will show you a pop-up with an explanation about cookies. You do have the right to opt-out and to object against the further use of non-functional cookies.',
  ),
  array(
	  'subtitle' => 'Vendors',
	  'content' => "We participate in the Transparency & Consent Framework for the CCPA. Other, so-called 'downstream', participants may re-sell data that was sold by us, as a publisher. You can opt-out to the re-sale of this data on the property of the participant as shown below."
					.'[cmplz-tcf-us-vendors]',
        'p' => false,
        'callback_condition' => 'cmplz_tcf_active',
        'condition' => array(
        	'us_states' => 'NOT EMPTY',
        )
  ),
  array(
    'subtitle' => 'Manage your opt-out preferences',
    'content' => '[cmplz-manage-consent]',
    'p' => false,
  ),

	array(
		'title' => 'Enabling/disabling and deleting cookies',
		'content' => 'You can use your internet browser to automatically or manually delete cookies. You can also specify that certain cookies may not be placed. Another option is to change the settings of your internet browser so that you receive a message each time a cookie is placed. For more information about these options, please refer to the instructions in the Help section of your browser.',
	),

	'enable-disable-removal-cookies-2' => array(
		'content' => _x('Please note that our website may not work properly if all cookies are disabled. If you do delete the cookies in your browser, they will be placed again after your consent when you visit our website again.', 'Legal document cookie policy', 'complianz-gdpr'),
	),


    array(
        'title' => 'Your rights with respect to personal data',
        'p'=> false,
        'content' =>
            '<p>You have the following rights with respect to your personal data:</p>' .
            '<ul>
                <li>you may submit a request for access to the data we process about you;</li>
                <li>you may object to the processing;</li>
                <li>you may request an overview, in a commonly used format, of the data we process about you;</li>
                <li>you may request correction or deletion of the data if it is incorrect or not or no longer relevant, or to ask to restrict the processing of the data.</li>
            </ul>' .
            '<p>To exercise these rights, please contact us. Please refer to the contact details at the bottom of this Cookie Policy. If you have a complaint about how we handle your data, we would like to hear from you.</p>'
          ),
    /* Privacy Statement */
    array(
      'content' => cmplz_sprintf('For more information about your rights with respect to personal data, please refer to our %sPrivacy Statement%s', '<a href="[privacy-statement-url]" target="_blank">', '</a>'),
        'condition' => array('privacy-statement' => 'NOT no'),
    ),

    /* No Privacy Statement */
    array(
      'content' => 'For more information about your rights with respect to personal data, please refer to our Privacy Statement',
        'condition' => array('privacy-statement' => 'no'),
    ),

	array(
        'title' => 'Contact details',
        'content' => 'For questions and/or comments about our Cookie Policy and this statement, please contact us by using the following contact details:',
    ),

    array(
        'content' => '<span class="cmplz-contact-organisation">[organisation_name]</span><br />
                      <span class="cmplz-contact-address">[address_company]</span><br />
                      <span class="cmplz-contact-country">[country_company]</span><br />
                      <span class="cmplz-contact-domain">Website: [domain]</span><br />
                      <span class="cmplz-contact-email">Email: [email_company]</span><br />
                      <span class="cmplz-contact-telephone">[telephone_company]</span>',
    ),

    array(
        'content' => cmplz_sprintf('This Cookie Policy was synchronized with %scookiedatabase.org%s on %s.', '<a href="https://cookiedatabase.org/" target="_blank">', '</a>', '[sync_date]'),
        'callback_condition' => array(
	        'cmplz_cdb_reference_in_policy',
        )
    ),

);
