<?php
/**
 * Kind_Fields class.
 *
 * @package Post_Kinds
 * @since   3.3.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generates form fields for the posting UI from a kind's property schema.
 *
 * The schemas are editor-agnostic: the same field definitions (exposed by
 * the post-kinds/1.0/fields REST route) can drive the Classic Editor, a block
 * editor UI or another editor. The field_*() methods render them as HTML for
 * the Classic Editor.
 *
 * A field definition has a 'type' and a 'label'; other keys depend on the type.
 * Supported types, one for each field_*() method:
 * - text, textarea, url
 * - number: `step` (default 1).
 * - select: `options`, an associative array of value => label.
 * - list: values separated by semicolons.
 * - datetime
 * - duration: `pieces`, which of Y, M, D, H, I (minutes) and S to show. Default H, I and S.
 * - author: a name, URL and photo.
 * - cite, venue: nested fields in `properties`.
 *
 * Any other type is treated as text. Any field can have `required` set to
 * true; fields are optional otherwise. See missing().
 *
 * @since 3.3.4
 */
class Kind_Fields {

	/**
	 * Returns a list of time zones with translated labels.
	 *
	 * Only time zones the host's time zone database knows are listed.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 Uses current identifiers, and leaves out unknown ones.
	 *
	 * @return string[] Labels, keyed by time zone identifier.
	 */
	public static function timezone_list() {
		$zones = array(
			'Pacific/Niue'                   => __( '(GMT-11:00) Niue', 'indieweb-post-kinds' ),
			'Pacific/Pago_Pago'              => __( '(GMT-11:00) Pago Pago', 'indieweb-post-kinds' ),
			'Pacific/Honolulu'               => __( '(GMT-10:00) Hawaii Time', 'indieweb-post-kinds' ),
			'Pacific/Rarotonga'              => __( '(GMT-10:00) Rarotonga', 'indieweb-post-kinds' ),
			'Pacific/Tahiti'                 => __( '(GMT-10:00) Tahiti', 'indieweb-post-kinds' ),
			'Pacific/Marquesas'              => __( '(GMT-09:30) Marquesas', 'indieweb-post-kinds' ),
			'America/Anchorage'              => __( '(GMT-09:00) Alaska Time', 'indieweb-post-kinds' ),
			'Pacific/Gambier'                => __( '(GMT-09:00) Gambier', 'indieweb-post-kinds' ),
			'America/Los_Angeles'            => __( '(GMT-08:00) Pacific Time', 'indieweb-post-kinds' ),
			'America/Tijuana'                => __( '(GMT-08:00) Pacific Time - Tijuana', 'indieweb-post-kinds' ),
			'America/Vancouver'              => __( '(GMT-08:00) Pacific Time - Vancouver', 'indieweb-post-kinds' ),
			'America/Whitehorse'             => __( '(GMT-08:00) Pacific Time - Whitehorse', 'indieweb-post-kinds' ),
			'Pacific/Pitcairn'               => __( '(GMT-08:00) Pitcairn', 'indieweb-post-kinds' ),
			'America/Dawson_Creek'           => __( '(GMT-07:00) Mountain Time - Dawson Creek', 'indieweb-post-kinds' ),
			'America/Denver'                 => __( '(GMT-07:00) Mountain Time', 'indieweb-post-kinds' ),
			'America/Edmonton'               => __( '(GMT-07:00) Mountain Time - Edmonton', 'indieweb-post-kinds' ),
			'America/Hermosillo'             => __( '(GMT-07:00) Mountain Time - Hermosillo', 'indieweb-post-kinds' ),
			'America/Mazatlan'               => __( '(GMT-07:00) Mountain Time - Chihuahua, Mazatlan', 'indieweb-post-kinds' ),
			'America/Phoenix'                => __( '(GMT-07:00) Mountain Time - Arizona', 'indieweb-post-kinds' ),
			'America/Yellowknife'            => __( '(GMT-07:00) Mountain Time - Yellowknife', 'indieweb-post-kinds' ),
			'America/Belize'                 => __( '(GMT-06:00) Belize', 'indieweb-post-kinds' ),
			'America/Chicago'                => __( '(GMT-06:00) Central Time', 'indieweb-post-kinds' ),
			'America/Costa_Rica'             => __( '(GMT-06:00) Costa Rica', 'indieweb-post-kinds' ),
			'America/El_Salvador'            => __( '(GMT-06:00) El Salvador', 'indieweb-post-kinds' ),
			'America/Guatemala'              => __( '(GMT-06:00) Guatemala', 'indieweb-post-kinds' ),
			'America/Managua'                => __( '(GMT-06:00) Managua', 'indieweb-post-kinds' ),
			'America/Mexico_City'            => __( '(GMT-06:00) Central Time - Mexico City', 'indieweb-post-kinds' ),
			'America/Regina'                 => __( '(GMT-06:00) Central Time - Regina', 'indieweb-post-kinds' ),
			'America/Tegucigalpa'            => __( '(GMT-06:00) Central Time - Tegucigalpa', 'indieweb-post-kinds' ),
			'America/Winnipeg'               => __( '(GMT-06:00) Central Time - Winnipeg', 'indieweb-post-kinds' ),
			'Pacific/Galapagos'              => __( '(GMT-06:00) Galapagos', 'indieweb-post-kinds' ),
			'America/Bogota'                 => __( '(GMT-05:00) Bogota', 'indieweb-post-kinds' ),
			'America/Cancun'                 => __( '(GMT-05:00) America Cancun', 'indieweb-post-kinds' ),
			'America/Cayman'                 => __( '(GMT-05:00) Cayman', 'indieweb-post-kinds' ),
			'America/Guayaquil'              => __( '(GMT-05:00) Guayaquil', 'indieweb-post-kinds' ),
			'America/Havana'                 => __( '(GMT-05:00) Havana', 'indieweb-post-kinds' ),
			'America/Iqaluit'                => __( '(GMT-05:00) Eastern Time - Iqaluit', 'indieweb-post-kinds' ),
			'America/Jamaica'                => __( '(GMT-05:00) Jamaica', 'indieweb-post-kinds' ),
			'America/Lima'                   => __( '(GMT-05:00) Lima', 'indieweb-post-kinds' ),
			'America/Nassau'                 => __( '(GMT-05:00) Nassau', 'indieweb-post-kinds' ),
			'America/New_York'               => __( '(GMT-05:00) Eastern Time', 'indieweb-post-kinds' ),
			'America/Panama'                 => __( '(GMT-05:00) Panama', 'indieweb-post-kinds' ),
			'America/Port-au-Prince'         => __( '(GMT-05:00) Port-au-Prince', 'indieweb-post-kinds' ),
			'America/Rio_Branco'             => __( '(GMT-05:00) Rio Branco', 'indieweb-post-kinds' ),
			'America/Toronto'                => __( '(GMT-05:00) Eastern Time - Toronto', 'indieweb-post-kinds' ),
			'Pacific/Easter'                 => __( '(GMT-05:00) Easter Island', 'indieweb-post-kinds' ),
			'America/Caracas'                => __( '(GMT-04:30) Caracas', 'indieweb-post-kinds' ),
			'America/Asuncion'               => __( '(GMT-03:00) Asuncion', 'indieweb-post-kinds' ),
			'America/Barbados'               => __( '(GMT-04:00) Barbados', 'indieweb-post-kinds' ),
			'America/Boa_Vista'              => __( '(GMT-04:00) Boa Vista', 'indieweb-post-kinds' ),
			'America/Campo_Grande'           => __( '(GMT-03:00) Campo Grande', 'indieweb-post-kinds' ),
			'America/Cuiaba'                 => __( '(GMT-03:00) Cuiaba', 'indieweb-post-kinds' ),
			'America/Curacao'                => __( '(GMT-04:00) Curacao', 'indieweb-post-kinds' ),
			'America/Grand_Turk'             => __( '(GMT-04:00) Grand Turk', 'indieweb-post-kinds' ),
			'America/Guyana'                 => __( '(GMT-04:00) Guyana', 'indieweb-post-kinds' ),
			'America/Halifax'                => __( '(GMT-04:00) Atlantic Time - Halifax', 'indieweb-post-kinds' ),
			'America/La_Paz'                 => __( '(GMT-04:00) La Paz', 'indieweb-post-kinds' ),
			'America/Manaus'                 => __( '(GMT-04:00) Manaus', 'indieweb-post-kinds' ),
			'America/Martinique'             => __( '(GMT-04:00) Martinique', 'indieweb-post-kinds' ),
			'America/Port_of_Spain'          => __( '(GMT-04:00) Port of Spain', 'indieweb-post-kinds' ),
			'America/Porto_Velho'            => __( '(GMT-04:00) Porto Velho', 'indieweb-post-kinds' ),
			'America/Puerto_Rico'            => __( '(GMT-04:00) Puerto Rico', 'indieweb-post-kinds' ),
			'America/Santo_Domingo'          => __( '(GMT-04:00) Santo Domingo', 'indieweb-post-kinds' ),
			'America/Thule'                  => __( '(GMT-04:00) Thule', 'indieweb-post-kinds' ),
			'Atlantic/Bermuda'               => __( '(GMT-04:00) Bermuda', 'indieweb-post-kinds' ),
			'America/St_Johns'               => __( '(GMT-03:30) Newfoundland Time - St. Johns', 'indieweb-post-kinds' ),
			'America/Araguaina'              => __( '(GMT-03:00) Araguaina', 'indieweb-post-kinds' ),
			'America/Argentina/Buenos_Aires' => __( '(GMT-03:00) Buenos Aires', 'indieweb-post-kinds' ),
			'America/Bahia'                  => __( '(GMT-03:00) Salvador', 'indieweb-post-kinds' ),
			'America/Belem'                  => __( '(GMT-03:00) Belem', 'indieweb-post-kinds' ),
			'America/Cayenne'                => __( '(GMT-03:00) Cayenne', 'indieweb-post-kinds' ),
			'America/Fortaleza'              => __( '(GMT-03:00) Fortaleza', 'indieweb-post-kinds' ),
			'America/Nuuk'                   => __( '(GMT-02:00) Nuuk', 'indieweb-post-kinds' ),
			'America/Maceio'                 => __( '(GMT-03:00) Maceio', 'indieweb-post-kinds' ),
			'America/Miquelon'               => __( '(GMT-03:00) Miquelon', 'indieweb-post-kinds' ),
			'America/Montevideo'             => __( '(GMT-03:00) Montevideo', 'indieweb-post-kinds' ),
			'America/Paramaribo'             => __( '(GMT-03:00) Paramaribo', 'indieweb-post-kinds' ),
			'America/Recife'                 => __( '(GMT-03:00) Recife', 'indieweb-post-kinds' ),
			'America/Santiago'               => __( '(GMT-03:00) Santiago', 'indieweb-post-kinds' ),
			'America/Sao_Paulo'              => __( '(GMT-02:00) Sao Paulo', 'indieweb-post-kinds' ),
			'Antarctica/Palmer'              => __( '(GMT-03:00) Palmer', 'indieweb-post-kinds' ),
			'Antarctica/Rothera'             => __( '(GMT-03:00) Rothera', 'indieweb-post-kinds' ),
			'Atlantic/Stanley'               => __( '(GMT-03:00) Stanley', 'indieweb-post-kinds' ),
			'America/Noronha'                => __( '(GMT-02:00) Noronha', 'indieweb-post-kinds' ),
			'Atlantic/South_Georgia'         => __( '(GMT-02:00) South Georgia', 'indieweb-post-kinds' ),
			'America/Scoresbysund'           => __( '(GMT-01:00) Scoresbysund', 'indieweb-post-kinds' ),
			'Atlantic/Azores'                => __( '(GMT-01:00) Azores', 'indieweb-post-kinds' ),
			'Atlantic/Cape_Verde'            => __( '(GMT-01:00) Cape Verde', 'indieweb-post-kinds' ),
			'Africa/Abidjan'                 => __( '(GMT+00:00) Abidjan', 'indieweb-post-kinds' ),
			'Africa/Accra'                   => __( '(GMT+00:00) Accra', 'indieweb-post-kinds' ),
			'Africa/Bissau'                  => __( '(GMT+00:00) Bissau', 'indieweb-post-kinds' ),
			'Africa/Casablanca'              => __( '(GMT+00:00) Casablanca', 'indieweb-post-kinds' ),
			'Africa/El_Aaiun'                => __( '(GMT+00:00) El Aaiun', 'indieweb-post-kinds' ),
			'Africa/Monrovia'                => __( '(GMT+00:00) Monrovia', 'indieweb-post-kinds' ),
			'America/Danmarkshavn'           => __( '(GMT+00:00) Danmarkshavn', 'indieweb-post-kinds' ),
			'Atlantic/Canary'                => __( '(GMT+00:00) Canary Islands', 'indieweb-post-kinds' ),
			'Atlantic/Faroe'                 => __( '(GMT+00:00) Faeroe', 'indieweb-post-kinds' ),
			'Atlantic/Reykjavik'             => __( '(GMT+00:00) Reykjavik', 'indieweb-post-kinds' ),
			'Etc/GMT'                        => __( '(GMT+00:00) GMT (no daylight saving)', 'indieweb-post-kinds' ),
			'Europe/Dublin'                  => __( '(GMT+00:00) Dublin', 'indieweb-post-kinds' ),
			'Europe/Lisbon'                  => __( '(GMT+00:00) Lisbon', 'indieweb-post-kinds' ),
			'Europe/London'                  => __( '(GMT+00:00) London', 'indieweb-post-kinds' ),
			'Africa/Algiers'                 => __( '(GMT+01:00) Algiers', 'indieweb-post-kinds' ),
			'Africa/Ceuta'                   => __( '(GMT+01:00) Ceuta', 'indieweb-post-kinds' ),
			'Africa/Lagos'                   => __( '(GMT+01:00) Lagos', 'indieweb-post-kinds' ),
			'Africa/Ndjamena'                => __( '(GMT+01:00) Ndjamena', 'indieweb-post-kinds' ),
			'Africa/Tunis'                   => __( '(GMT+01:00) Tunis', 'indieweb-post-kinds' ),
			'Africa/Windhoek'                => __( '(GMT+02:00) Windhoek', 'indieweb-post-kinds' ),
			'Europe/Amsterdam'               => __( '(GMT+01:00) Amsterdam', 'indieweb-post-kinds' ),
			'Europe/Andorra'                 => __( '(GMT+01:00) Andorra', 'indieweb-post-kinds' ),
			'Europe/Belgrade'                => __( '(GMT+01:00) Central European Time - Belgrade', 'indieweb-post-kinds' ),
			'Europe/Berlin'                  => __( '(GMT+01:00) Berlin', 'indieweb-post-kinds' ),
			'Europe/Brussels'                => __( '(GMT+01:00) Brussels', 'indieweb-post-kinds' ),
			'Europe/Budapest'                => __( '(GMT+01:00) Budapest', 'indieweb-post-kinds' ),
			'Europe/Copenhagen'              => __( '(GMT+01:00) Copenhagen', 'indieweb-post-kinds' ),
			'Europe/Gibraltar'               => __( '(GMT+01:00) Gibraltar', 'indieweb-post-kinds' ),
			'Europe/Luxembourg'              => __( '(GMT+01:00) Luxembourg', 'indieweb-post-kinds' ),
			'Europe/Madrid'                  => __( '(GMT+01:00) Madrid', 'indieweb-post-kinds' ),
			'Europe/Malta'                   => __( '(GMT+01:00) Malta', 'indieweb-post-kinds' ),
			'Europe/Monaco'                  => __( '(GMT+01:00) Monaco', 'indieweb-post-kinds' ),
			'Europe/Oslo'                    => __( '(GMT+01:00) Oslo', 'indieweb-post-kinds' ),
			'Europe/Paris'                   => __( '(GMT+01:00) Paris', 'indieweb-post-kinds' ),
			'Europe/Prague'                  => __( '(GMT+01:00) Central European Time - Prague', 'indieweb-post-kinds' ),
			'Europe/Rome'                    => __( '(GMT+01:00) Rome', 'indieweb-post-kinds' ),
			'Europe/Stockholm'               => __( '(GMT+01:00) Stockholm', 'indieweb-post-kinds' ),
			'Europe/Tirane'                  => __( '(GMT+01:00) Tirane', 'indieweb-post-kinds' ),
			'Europe/Vienna'                  => __( '(GMT+01:00) Vienna', 'indieweb-post-kinds' ),
			'Europe/Warsaw'                  => __( '(GMT+01:00) Warsaw', 'indieweb-post-kinds' ),
			'Europe/Zurich'                  => __( '(GMT+01:00) Zurich', 'indieweb-post-kinds' ),
			'Africa/Cairo'                   => __( '(GMT+02:00) Cairo', 'indieweb-post-kinds' ),
			'Africa/Johannesburg'            => __( '(GMT+02:00) Johannesburg', 'indieweb-post-kinds' ),
			'Africa/Maputo'                  => __( '(GMT+02:00) Maputo', 'indieweb-post-kinds' ),
			'Africa/Tripoli'                 => __( '(GMT+02:00) Tripoli', 'indieweb-post-kinds' ),
			'Asia/Amman'                     => __( '(GMT+02:00) Amman', 'indieweb-post-kinds' ),
			'Asia/Beirut'                    => __( '(GMT+02:00) Beirut', 'indieweb-post-kinds' ),
			'Asia/Damascus'                  => __( '(GMT+02:00) Damascus', 'indieweb-post-kinds' ),
			'Asia/Gaza'                      => __( '(GMT+02:00) Gaza', 'indieweb-post-kinds' ),
			'Asia/Jerusalem'                 => __( '(GMT+02:00) Jerusalem', 'indieweb-post-kinds' ),
			'Asia/Nicosia'                   => __( '(GMT+02:00) Nicosia', 'indieweb-post-kinds' ),
			'Europe/Athens'                  => __( '(GMT+02:00) Athens', 'indieweb-post-kinds' ),
			'Europe/Bucharest'               => __( '(GMT+02:00) Bucharest', 'indieweb-post-kinds' ),
			'Europe/Chisinau'                => __( '(GMT+02:00) Chisinau', 'indieweb-post-kinds' ),
			'Europe/Helsinki'                => __( '(GMT+02:00) Helsinki', 'indieweb-post-kinds' ),
			'Europe/Istanbul'                => __( '(GMT+02:00) Istanbul', 'indieweb-post-kinds' ),
			'Europe/Kaliningrad'             => __( '(GMT+02:00) Moscow-01 - Kaliningrad', 'indieweb-post-kinds' ),
			'Europe/Kyiv'                    => __( '(GMT+02:00) Kyiv', 'indieweb-post-kinds' ),
			'Europe/Riga'                    => __( '(GMT+02:00) Riga', 'indieweb-post-kinds' ),
			'Europe/Sofia'                   => __( '(GMT+02:00) Sofia', 'indieweb-post-kinds' ),
			'Europe/Tallinn'                 => __( '(GMT+02:00) Tallinn', 'indieweb-post-kinds' ),
			'Europe/Vilnius'                 => __( '(GMT+02:00) Vilnius', 'indieweb-post-kinds' ),
			'Africa/Khartoum'                => __( '(GMT+03:00) Khartoum', 'indieweb-post-kinds' ),
			'Africa/Nairobi'                 => __( '(GMT+03:00) Nairobi', 'indieweb-post-kinds' ),
			'Antarctica/Syowa'               => __( '(GMT+03:00) Syowa', 'indieweb-post-kinds' ),
			'Asia/Baghdad'                   => __( '(GMT+03:00) Baghdad', 'indieweb-post-kinds' ),
			'Asia/Qatar'                     => __( '(GMT+03:00) Qatar', 'indieweb-post-kinds' ),
			'Asia/Riyadh'                    => __( '(GMT+03:00) Riyadh', 'indieweb-post-kinds' ),
			'Europe/Minsk'                   => __( '(GMT+03:00) Minsk', 'indieweb-post-kinds' ),
			'Europe/Moscow'                  => __( '(GMT+03:00) Moscow+00 - Moscow', 'indieweb-post-kinds' ),
			'Asia/Tehran'                    => __( '(GMT+03:30) Tehran', 'indieweb-post-kinds' ),
			'Asia/Baku'                      => __( '(GMT+04:00) Baku', 'indieweb-post-kinds' ),
			'Asia/Dubai'                     => __( '(GMT+04:00) Dubai', 'indieweb-post-kinds' ),
			'Asia/Tbilisi'                   => __( '(GMT+04:00) Tbilisi', 'indieweb-post-kinds' ),
			'Asia/Yerevan'                   => __( '(GMT+04:00) Yerevan', 'indieweb-post-kinds' ),
			'Europe/Samara'                  => __( '(GMT+04:00) Moscow+01 - Samara', 'indieweb-post-kinds' ),
			'Indian/Mahe'                    => __( '(GMT+04:00) Mahe', 'indieweb-post-kinds' ),
			'Indian/Mauritius'               => __( '(GMT+04:00) Mauritius', 'indieweb-post-kinds' ),
			'Indian/Reunion'                 => __( '(GMT+04:00) Reunion', 'indieweb-post-kinds' ),
			'Asia/Kabul'                     => __( '(GMT+04:30) Kabul', 'indieweb-post-kinds' ),
			'Antarctica/Mawson'              => __( '(GMT+05:00) Mawson', 'indieweb-post-kinds' ),
			'Asia/Aqtau'                     => __( '(GMT+05:00) Aqtau', 'indieweb-post-kinds' ),
			'Asia/Aqtobe'                    => __( '(GMT+05:00) Aqtobe', 'indieweb-post-kinds' ),
			'Asia/Ashgabat'                  => __( '(GMT+05:00) Ashgabat', 'indieweb-post-kinds' ),
			'Asia/Dushanbe'                  => __( '(GMT+05:00) Dushanbe', 'indieweb-post-kinds' ),
			'Asia/Karachi'                   => __( '(GMT+05:00) Karachi', 'indieweb-post-kinds' ),
			'Asia/Tashkent'                  => __( '(GMT+05:00) Tashkent', 'indieweb-post-kinds' ),
			'Asia/Yekaterinburg'             => __( '(GMT+05:00) Moscow+02 - Yekaterinburg', 'indieweb-post-kinds' ),
			'Indian/Kerguelen'               => __( '(GMT+05:00) Kerguelen', 'indieweb-post-kinds' ),
			'Indian/Maldives'                => __( '(GMT+05:00) Maldives', 'indieweb-post-kinds' ),
			'Asia/Kolkata'                   => __( '(GMT+05:30) India Standard Time', 'indieweb-post-kinds' ),
			'Asia/Colombo'                   => __( '(GMT+05:30) Colombo', 'indieweb-post-kinds' ),
			'Asia/Kathmandu'                 => __( '(GMT+05:45) Kathmandu', 'indieweb-post-kinds' ),
			'Antarctica/Vostok'              => __( '(GMT+06:00) Vostok', 'indieweb-post-kinds' ),
			'Asia/Almaty'                    => __( '(GMT+06:00) Almaty', 'indieweb-post-kinds' ),
			'Asia/Bishkek'                   => __( '(GMT+06:00) Bishkek', 'indieweb-post-kinds' ),
			'Asia/Dhaka'                     => __( '(GMT+06:00) Dhaka', 'indieweb-post-kinds' ),
			'Asia/Omsk'                      => __( '(GMT+06:00) Moscow+03 - Omsk, Novosibirsk', 'indieweb-post-kinds' ),
			'Asia/Thimphu'                   => __( '(GMT+06:00) Thimphu', 'indieweb-post-kinds' ),
			'Indian/Chagos'                  => __( '(GMT+06:00) Chagos', 'indieweb-post-kinds' ),
			'Asia/Yangon'                    => __( '(GMT+06:30) Yangon', 'indieweb-post-kinds' ),
			'Indian/Cocos'                   => __( '(GMT+06:30) Cocos', 'indieweb-post-kinds' ),
			'Antarctica/Davis'               => __( '(GMT+07:00) Davis', 'indieweb-post-kinds' ),
			'Asia/Bangkok'                   => __( '(GMT+07:00) Bangkok', 'indieweb-post-kinds' ),
			'Asia/Hovd'                      => __( '(GMT+07:00) Hovd', 'indieweb-post-kinds' ),
			'Asia/Jakarta'                   => __( '(GMT+07:00) Jakarta', 'indieweb-post-kinds' ),
			'Asia/Krasnoyarsk'               => __( '(GMT+07:00) Moscow+04 - Krasnoyarsk', 'indieweb-post-kinds' ),
			'Asia/Ho_Chi_Minh'               => __( '(GMT+07:00) Ho Chi Minh', 'indieweb-post-kinds' ),
			'Indian/Christmas'               => __( '(GMT+07:00) Christmas', 'indieweb-post-kinds' ),
			'Antarctica/Casey'               => __( '(GMT+08:00) Casey', 'indieweb-post-kinds' ),
			'Asia/Brunei'                    => __( '(GMT+08:00) Brunei', 'indieweb-post-kinds' ),
			'Asia/Hong_Kong'                 => __( '(GMT+08:00) Hong Kong', 'indieweb-post-kinds' ),
			'Asia/Irkutsk'                   => __( '(GMT+08:00) Moscow+05 - Irkutsk', 'indieweb-post-kinds' ),
			'Asia/Kuala_Lumpur'              => __( '(GMT+08:00) Kuala Lumpur', 'indieweb-post-kinds' ),
			'Asia/Macau'                     => __( '(GMT+08:00) Macau', 'indieweb-post-kinds' ),
			'Asia/Makassar'                  => __( '(GMT+08:00) Makassar', 'indieweb-post-kinds' ),
			'Asia/Manila'                    => __( '(GMT+08:00) Manila', 'indieweb-post-kinds' ),
			'Asia/Shanghai'                  => __( '(GMT+08:00) China Time - Beijing', 'indieweb-post-kinds' ),
			'Asia/Singapore'                 => __( '(GMT+08:00) Singapore', 'indieweb-post-kinds' ),
			'Asia/Taipei'                    => __( '(GMT+08:00) Taipei', 'indieweb-post-kinds' ),
			'Asia/Ulaanbaatar'               => __( '(GMT+08:00) Ulaanbaatar', 'indieweb-post-kinds' ),
			'Australia/Perth'                => __( '(GMT+08:00) Western Time - Perth', 'indieweb-post-kinds' ),
			'Asia/Pyongyang'                 => __( '(GMT+08:30) Pyongyang', 'indieweb-post-kinds' ),
			'Asia/Dili'                      => __( '(GMT+09:00) Dili', 'indieweb-post-kinds' ),
			'Asia/Jayapura'                  => __( '(GMT+09:00) Jayapura', 'indieweb-post-kinds' ),
			'Asia/Seoul'                     => __( '(GMT+09:00) Seoul', 'indieweb-post-kinds' ),
			'Asia/Tokyo'                     => __( '(GMT+09:00) Tokyo', 'indieweb-post-kinds' ),
			'Asia/Yakutsk'                   => __( '(GMT+09:00) Moscow+06 - Yakutsk', 'indieweb-post-kinds' ),
			'Pacific/Palau'                  => __( '(GMT+09:00) Palau', 'indieweb-post-kinds' ),
			'Australia/Adelaide'             => __( '(GMT+10:30) Central Time - Adelaide', 'indieweb-post-kinds' ),
			'Australia/Darwin'               => __( '(GMT+09:30) Central Time - Darwin', 'indieweb-post-kinds' ),
			'Antarctica/DumontDUrville'      => "(GMT+10:00) Dumont D'Urville",
			'Asia/Magadan'                   => __( '(GMT+10:00) Moscow+07 - Magadan', 'indieweb-post-kinds' ),
			'Asia/Vladivostok'               => __( '(GMT+10:00) Moscow+07 - Yuzhno-Sakhalinsk', 'indieweb-post-kinds' ),
			'Australia/Brisbane'             => __( '(GMT+10:00) Eastern Time - Brisbane', 'indieweb-post-kinds' ),
			'Australia/Hobart'               => __( '(GMT+11:00) Eastern Time - Hobart', 'indieweb-post-kinds' ),
			'Australia/Sydney'               => __( '(GMT+11:00) Eastern Time - Melbourne, Sydney', 'indieweb-post-kinds' ),
			'Pacific/Chuuk'                  => __( '(GMT+10:00) Truk', 'indieweb-post-kinds' ),
			'Pacific/Guam'                   => __( '(GMT+10:00) Guam', 'indieweb-post-kinds' ),
			'Pacific/Port_Moresby'           => __( '(GMT+10:00) Port Moresby', 'indieweb-post-kinds' ),
			'Pacific/Efate'                  => __( '(GMT+11:00) Efate', 'indieweb-post-kinds' ),
			'Pacific/Guadalcanal'            => __( '(GMT+11:00) Guadalcanal', 'indieweb-post-kinds' ),
			'Pacific/Kosrae'                 => __( '(GMT+11:00) Kosrae', 'indieweb-post-kinds' ),
			'Pacific/Norfolk'                => __( '(GMT+11:00) Norfolk', 'indieweb-post-kinds' ),
			'Pacific/Noumea'                 => __( '(GMT+11:00) Noumea', 'indieweb-post-kinds' ),
			'Pacific/Pohnpei'                => __( '(GMT+11:00) Ponape', 'indieweb-post-kinds' ),
			'Asia/Kamchatka'                 => __( '(GMT+12:00) Moscow+09 - Petropavlovsk-Kamchatskiy', 'indieweb-post-kinds' ),
			'Pacific/Auckland'               => __( '(GMT+13:00) Auckland', 'indieweb-post-kinds' ),
			'Pacific/Fiji'                   => __( '(GMT+13:00) Fiji', 'indieweb-post-kinds' ),
			'Pacific/Funafuti'               => __( '(GMT+12:00) Funafuti', 'indieweb-post-kinds' ),
			'Pacific/Kwajalein'              => __( '(GMT+12:00) Kwajalein', 'indieweb-post-kinds' ),
			'Pacific/Majuro'                 => __( '(GMT+12:00) Majuro', 'indieweb-post-kinds' ),
			'Pacific/Nauru'                  => __( '(GMT+12:00) Nauru', 'indieweb-post-kinds' ),
			'Pacific/Tarawa'                 => __( '(GMT+12:00) Tarawa', 'indieweb-post-kinds' ),
			'Pacific/Wake'                   => __( '(GMT+12:00) Wake', 'indieweb-post-kinds' ),
			'Pacific/Wallis'                 => __( '(GMT+12:00) Wallis', 'indieweb-post-kinds' ),
			'Pacific/Apia'                   => __( '(GMT+14:00) Apia', 'indieweb-post-kinds' ),
			'Pacific/Kanton'                 => __( '(GMT+13:00) Kanton', 'indieweb-post-kinds' ),
			'Pacific/Fakaofo'                => __( '(GMT+13:00) Fakaofo', 'indieweb-post-kinds' ),
			'Pacific/Tongatapu'              => __( '(GMT+13:00) Tongatapu', 'indieweb-post-kinds' ),
			'Pacific/Kiritimati'             => __( '(GMT+14:00) Kiritimati', 'indieweb-post-kinds' ),
		);

		// Identifiers the host's time zone database doesn't know, such as names added after it was built, are left out.
		$known = array();
		foreach ( array_keys( $zones ) as $zone ) {
			try {
				new DateTimeZone( $zone );
				$known[ $zone ] = true;
			} catch ( Exception $e ) {
				continue;
			}
		}
		return array_intersect_key( $zones, $known );
	}

	/**
	 * Returns the UTC offsets in use by any time zone, sorted.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 Uses get_gmt_offsets().
	 *
	 * @return string[] Offsets such as '-05:00'.
	 */
	public static function get_offset_list() {
		return get_gmt_offsets();
	}

	/**
	 * Returns an id attribute for a field name, such as mf2_in-reply-to-name for mf2_in-reply-to[name].
	 *
	 * @since 4.0.2
	 *
	 * @param string $name Field name.
	 * @return string
	 */
	private static function field_id( $name ) {
		return trim( preg_replace( '/[^A-Za-z0-9_-]+/', '-', $name ), '-' );
	}

	/**
	 * Returns the opening label for a field.
	 *
	 * @since 4.0.2
	 *
	 * @param array  $args Field arguments, as returned by validate().
	 * @param string $for  Optional. The id the label is for. Default the field's id.
	 * @return string
	 */
	private static function label( $args, $for = null ) {
		return sprintf(
			'<label for="%1$s" class="%2$s">%3$s',
			esc_attr( $for ?? $args['id'] ),
			esc_attr( self::classes( $args ) ),
			esc_html( $args['label'] )
		);
	}

	/**
	 * Returns a field's classes, with kind-field-required added for a required field.
	 *
	 * @since 4.0.2
	 *
	 * @param array  $args  Field arguments, as returned by validate().
	 * @param string $extra Optional. Classes to add first. Default empty.
	 * @return string
	 */
	private static function classes( $args, $extra = '' ) {
		$classes = array( $extra, $args['class'], empty( $args['required'] ) ? '' : 'kind-field-required' );
		return implode( ' ', array_filter( array_map( 'trim', $classes ), 'strlen' ) );
	}

	/**
	 * Returns the aria-required attribute for a required field, or an empty string.
	 *
	 * The HTML required attribute isn't used: it would stop a draft from being
	 * saved while the field is empty.
	 *
	 * @since 4.0.2
	 *
	 * @param array $args Field arguments, as returned by validate().
	 * @return string
	 */
	private static function aria_required( $args ) {
		return empty( $args['required'] ) ? '' : ' aria-required="true"';
	}

	/**
	 * Renders date, time and offset inputs.
	 *
	 * The parts are named {name}[date], {name}[time] and {name}[offset].
	 *
	 * @since 3.3.4
	 * @since 4.0.2 The inputs are empty when there is no date, instead of showing now.
	 *
	 * @param array                          $args     Field arguments, as returned by validate().
	 * @param DateTimeInterface|string|false $datetime Date, or a date string.
	 * @return string The inputs.
	 */
	public static function field_datetime( $args, $datetime ) {
		$time = Kind_Time::divide_datetime( $datetime );
		if ( ! $time ) {
			$time = array( 'offset' => Kind_Time::get_datetime_offset() );
		}
		$return   = array();
		$return[] = self::label( $args, $args['id'] . '-date' );
		$return[] = sprintf( '<input type="date" name="%1$s[date]" id="%2$s-date" value="%3$s"%4$s />', esc_attr( $args['name'] ), esc_attr( $args['id'] ), esc_attr( $time['date'] ?? '' ), self::aria_required( $args ) );
		$return[] = sprintf( '<input type="time" name="%1$s[time]" id="%2$s-time" step="1" value="%3$s" />', esc_attr( $args['name'] ), esc_attr( $args['id'] ), esc_attr( $time['time'] ?? '' ) );
		$return[] = sprintf( '<select name="%1$s[offset]" id="%2$s-offset">', esc_attr( $args['name'] ), esc_attr( $args['id'] ) );
		foreach ( self::get_offset_list() as $offset ) {
			$return[] = sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $offset ), selected( $offset, $time['offset'] ?? '', false ), esc_html( 'GMT' . $offset ) );
		}
		$return[] = '</select></label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Renders duration inputs, one for each of $args['pieces'].
	 *
	 * The parts are named {name}[Y], {name}[M], {name}[D], {name}[H], {name}[I] and {name}[S].
	 *
	 * @since 3.3.4
	 *
	 * @param array                     $args     Field arguments, as returned by validate(), including 'pieces'.
	 * @param DateInterval|string|false $interval Duration, or an ISO 8601 duration.
	 * @return string The inputs.
	 */
	public static function field_duration( $args, $interval ) {
		$duration = Kind_Time::divide_interval( kind_safe_interval( $interval ) );
		$pieces   = array(
			'Y' => array( 1000, __( 'Years', 'indieweb-post-kinds' ) ),
			'M' => array( 11, __( 'Months', 'indieweb-post-kinds' ) ),
			'D' => array( 31, __( 'Days', 'indieweb-post-kinds' ) ),
			'H' => array( 23, __( 'Hours', 'indieweb-post-kinds' ) ),
			'I' => array( 59, __( 'Minutes', 'indieweb-post-kinds' ) ),
			'S' => array( 59, __( 'Seconds', 'indieweb-post-kinds' ) ),
		);
		$first    = reset( $args['pieces'] );
		$return   = array();
		$return[] = self::label( $args, $args['id'] . '-' . $first );
		foreach ( $args['pieces'] as $piece ) {
			$return[] = sprintf(
				'<input type="number" name="%1$s[%2$s]" id="%3$s-%2$s" value="%4$s" min="0" max="%5$d" step="1" aria-label="%6$s" />',
				esc_attr( $args['name'] ),
				esc_attr( $piece ),
				esc_attr( $args['id'] ),
				esc_attr( $duration[ $piece ] ? (string) $duration[ $piece ] : '' ),
				$pieces[ $piece ][0],
				esc_attr( $pieces[ $piece ][1] )
			);
		}
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Returns the default fields of an author: name, URL and photo.
	 *
	 * @since 4.0.2
	 *
	 * @return array Field definitions, keyed by property.
	 */
	public static function author_properties() {
		return array(
			'name'  => array(
				'type'  => 'text',
				'label' => __( 'Author Name', 'indieweb-post-kinds' ),
			),
			'url'   => array(
				'type'  => 'url',
				'label' => __( 'Author URL', 'indieweb-post-kinds' ),
			),
			'photo' => array(
				'type'  => 'url',
				'label' => __( 'Author Photo', 'indieweb-post-kinds' ),
			),
		);
	}

	/**
	 * Renders author name, URL and photo inputs, named {name}[name], {name}[url] and {name}[photo].
	 *
	 * @since 3.3.4
	 *
	 * @param array              $args   Field arguments, as returned by validate().
	 * @param array|string|false $author An author as jf2 or mf2, or a name or URL. For a list, the first.
	 * @return string The inputs.
	 */
	public static function field_author( $args, $author ) {
		$authors = Kind_Post::normalize_authors( $author ? $author : array() );
		$author  = $authors ? $authors[0] : array();
		return self::fieldset( $args, self::author_properties(), $author );
	}

	/**
	 * Renders a select. The options are in $args['options'], value => label.
	 *
	 * @since 3.3.4
	 *
	 * @param array  $args     Field arguments, as returned by validate().
	 * @param string $selected Value of the selected option.
	 * @return string The input.
	 */
	public static function field_select( $args, $selected ) {
		$return   = array();
		$return[] = self::label( $args );
		$return[] = sprintf( '<select name="%1$s" id="%2$s"%3$s>', esc_attr( $args['name'] ), esc_attr( $args['id'] ), self::aria_required( $args ) );
		$return[] = '<option value=""></option>';
		foreach ( $args['options'] as $key => $value ) {
			$return[] = sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $key ), selected( (string) $key, (string) self::scalar( $selected ), false ), esc_html( $value ) );
		}
		$return[] = '</select></label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Renders an input.
	 *
	 * @since 4.0.2
	 *
	 * @param array  $args  Field arguments, as returned by validate().
	 * @param string $type  Input type.
	 * @param mixed  $value Value.
	 * @param string $extra Optional. Extra attributes, already escaped. Default empty.
	 * @return string The input.
	 */
	private static function input( $args, $type, $value, $extra = '' ) {
		return implode(
			PHP_EOL,
			array(
				self::label( $args ),
				sprintf( '<input name="%1$s" id="%2$s" type="%3$s" value="%4$s"%5$s%6$s />', esc_attr( $args['name'] ), esc_attr( $args['id'] ), esc_attr( $type ), esc_attr( self::scalar( $value ) ), $extra, self::aria_required( $args ) ),
				'</label>',
			)
		);
	}

	/**
	 * Renders a URL input.
	 *
	 * @since 3.3.4
	 *
	 * @param array  $args Field arguments, as returned by validate().
	 * @param string $url  Value.
	 * @return string The input.
	 */
	public static function field_url( $args, $url ) {
		return self::input( $args, 'url', $url );
	}

	/**
	 * Renders a number input. The step is in $args['step'].
	 *
	 * @since 3.3.4
	 *
	 * @param array      $args  Field arguments, as returned by validate().
	 * @param int|string $value Value.
	 * @return string The input.
	 */
	public static function field_number( $args, $value ) {
		return self::input( $args, 'number', $value, sprintf( ' step="%s"', esc_attr( $args['step'] ) ) );
	}

	/**
	 * Renders a text input.
	 *
	 * @since 3.3.4
	 *
	 * @param array  $args  Field arguments, as returned by validate().
	 * @param string $value Value.
	 * @return string The input.
	 */
	public static function field_text( $args, $value ) {
		return self::input( $args, 'text', $value );
	}

	/**
	 * Renders a textarea.
	 *
	 * @since 3.3.4
	 *
	 * @param array        $args  Field arguments, as returned by validate().
	 * @param string|array $value Value. For jf2 HTML values, the text ('value') is used.
	 * @return string The input.
	 */
	public static function field_textarea( $args, $value ) {
		if ( is_array( $value ) && isset( $value['value'] ) ) {
			$value = $value['value'];
		}
		return implode(
			PHP_EOL,
			array(
				self::label( $args ),
				sprintf( '<textarea name="%1$s" id="%2$s"%3$s>%4$s</textarea>', esc_attr( $args['name'] ), esc_attr( $args['id'] ), self::aria_required( $args ), esc_textarea( self::scalar( $value ) ) ),
				'</label>',
			)
		);
	}

	/**
	 * Renders a textarea for a list, with items separated by semicolons.
	 *
	 * @since 3.3.4
	 *
	 * @param array        $args  Field arguments, as returned by validate().
	 * @param string|array $value Value, or a list of values.
	 * @return string The input.
	 */
	public static function field_list( $args, $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ';', array_filter( $value, 'is_scalar' ) );
		}
		return self::field_textarea( $args, $value );
	}

	/**
	 * Renders the inputs for a venue's properties, in $args['properties'].
	 *
	 * @since 3.3.4
	 *
	 * @param array        $args  Field arguments, as returned by validate(), including 'properties'.
	 * @param array|string $value Values as jf2 or mf2, keyed by property.
	 * @return string The inputs.
	 */
	public static function field_venue( $args, $value ) {
		return self::fieldset( $args, $args['properties'], $value );
	}

	/**
	 * Renders the inputs for a citation's properties, in $args['properties'].
	 *
	 * @since 3.3.4
	 *
	 * @param array        $args  Field arguments, as returned by validate(), including 'properties'.
	 * @param array|string $value Values as jf2 or mf2, keyed by property. A URL is read as the citation's URL.
	 * @return string The inputs.
	 */
	public static function field_cite( $args, $value ) {
		return self::fieldset( $args, $args['properties'], $value );
	}

	/**
	 * Renders nested fields in a fieldset, with the field's label as its legend.
	 *
	 * @since 4.0.2
	 *
	 * @param array        $args       Field arguments, as returned by validate().
	 * @param array        $properties Field definitions of the nested fields.
	 * @param array|string $value      Values as jf2 or mf2, keyed by property.
	 * @return string The fieldset.
	 */
	private static function fieldset( $args, $properties, $value ) {
		if ( is_string( $value ) ) {
			$value = wp_http_validate_url( $value ) ? array( 'url' => $value ) : array( 'name' => $value );
		}
		if ( is_array( $value ) && wp_is_numeric_array( $value ) ) {
			$value = reset( $value );
		}
		if ( is_array( $value ) && isset( $value['properties'] ) && function_exists( '\\ParseThis\\mf2_to_jf2' ) ) {
			$value = \ParseThis\mf2_to_jf2( $value );
		}
		return sprintf(
			'<fieldset id="%1$s" class="%2$s"><legend>%3$s</legend>%4$s</fieldset>',
			esc_attr( $args['id'] ),
			esc_attr( self::classes( $args, 'kind-field-' . $args['type'] ) ),
			esc_html( $args['label'] ),
			self::render( $properties, is_array( $value ) ? $value : array(), $args['name'] )
		);
	}

	/**
	 * Returns a value as a string for a single input. Lists use their first value.
	 *
	 * @since 4.0.2
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function scalar( $value ) {
		while ( is_array( $value ) && ! empty( $value ) && wp_is_numeric_array( $value ) ) {
			$value = reset( $value );
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Checks whether a string starts with a prefix.
	 *
	 * @since 3.3.4
	 *
	 * @param string $source The source string.
	 * @param string $prefix The prefix to look for.
	 * @return bool True if the source starts with the prefix.
	 */
	public static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	/**
	 * Returns a value from an array.
	 *
	 * @since 3.3.4
	 *
	 * @param string $key   Key.
	 * @param array  $array The array.
	 * @return mixed The value, or false if it is not set.
	 */
	private static function get( $key, $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}
		return false;
	}

	/**
	 * Checks a field definition and fills in defaults.
	 *
	 * A field needs a type, and a name or a label; each defaults to the other.
	 * A type with no renderer is treated as text. Cite and venue fields need
	 * 'properties', and select fields 'options'.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 A label is no longer required, and unsupported types become text.
	 *
	 * @param array $element Field definition.
	 * @return array|false The field definition, or false if it is not valid.
	 */
	private static function validate( $element ) {
		if ( ! is_array( $element ) || ! self::get( 'type', $element ) ) {
			return false;
		}
		if ( ! self::get( 'name', $element ) ) {
			if ( ! self::get( 'label', $element ) ) {
				return false;
			}
			$element['name'] = sanitize_title( $element['label'] );
		}
		if ( ! self::get( 'label', $element ) ) {
			$element['label'] = $element['name'];
		}
		if ( ! self::get( 'id', $element ) ) {
			$element['id'] = self::field_id( $element['name'] );
		}
		if ( ! self::get( 'class', $element ) ) {
			$element['class'] = '';
		}
		if ( is_array( $element['class'] ) ) {
			$element['class'] = implode( ' ', $element['class'] );
		}
		$element['required'] = ! empty( $element['required'] );
		if ( ! self::supported_type( $element['type'] ) ) {
			$element['type'] = 'text';
		}
		switch ( $element['type'] ) {
			case 'cite':
			case 'venue':
				if ( ! is_array( self::get( 'properties', $element ) ) ) {
					return false;
				}
				break;
			case 'number':
				if ( ! array_key_exists( 'step', $element ) ) {
					$element['step'] = 1;
				}
				break;
			case 'select':
				if ( ! is_array( self::get( 'options', $element ) ) ) {
					return false;
				}
				break;
			case 'duration':
				$pieces            = is_array( self::get( 'pieces', $element ) ) ? $element['pieces'] : array( 'H', 'I', 'S' );
				$element['pieces'] = array_values( array_intersect( array( 'Y', 'M', 'D', 'H', 'I', 'S' ), $pieces ) );
				if ( empty( $element['pieces'] ) ) {
					$element['pieces'] = array( 'H', 'I', 'S' );
				}
				break;
		}
		return $element;
	}

	/**
	 * Whether a field type is supported: whether there is a field_{type}() renderer for it.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 Derived from the field_*() methods.
	 *
	 * @param string $type Field type.
	 * @return bool
	 */
	public static function supported_type( $type ) {
		return is_string( $type ) && 1 === preg_match( '/^[a-z]+$/', $type ) && method_exists( static::class, 'field_' . $type );
	}

	/**
	 * Renders form fields from a field schema.
	 *
	 * Fields are named mf2_{property}. Nested fields (an author, citation or
	 * venue) and the parts of a date or duration are named with sub-keys, such
	 * as mf2_in-reply-to[author][name], so form data arrives structured.
	 * Invalid field definitions are skipped.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 Added the $parent parameter; fields are named with sub-keys and escaped.
	 *
	 * @param array  $schema Field definitions, keyed by property.
	 * @param array  $values Optional. Values as jf2, keyed by property. Default empty array.
	 * @param string $parent Optional. Name of the field these are nested in. Default empty, for top-level fields.
	 * @return string The fields.
	 */
	public static function render( $schema, $values = array(), $parent = '' ) {
		$return = array();
		foreach ( (array) $schema as $key => $args ) {
			if ( ! is_array( $args ) ) {
				continue;
			}
			$key          = sanitize_key( $key );
			$args['name'] = '' === $parent ? 'mf2_' . $key : $parent . '[' . $key . ']';
			unset( $args['id'] );
			// A field without a label is labelled with its property, not its field name.
			if ( ! self::get( 'label', $args ) ) {
				$args['label'] = $key;
			}
			$args = self::validate( $args );
			if ( ! $args ) {
				continue;
			}
			$return[] = call_user_func( array( static::class, 'field_' . $args['type'] ), $args, self::get( $key, $values ) );
		}
		return implode( '<br />', $return );
	}

	/**
	 * Extracts microformats properties from submitted form data.
	 *
	 * Reads the fields named mf2_{property}. Nested values arrive as arrays
	 * (mf2_in-reply-to[name]); the older {property}_{part} form
	 * (mf2_start_date) is read too. Date parts (date, time, offset) become an
	 * ISO 8601 date, and duration parts (Y, M, D, H, I, S) an ISO 8601
	 * duration. Empty values are dropped.
	 *
	 * With a schema, the values are also sanitized by field type and only the
	 * schema's properties are kept; see sanitize(). Without one, values are
	 * only reassembled, not sanitized.
	 *
	 * @since 3.3.4
	 * @since 4.0.2 Rewritten; added the $schema parameter.
	 *
	 * @param array      $data   Form data, unslashed, such as wp_unslash( $_POST ).
	 * @param array|null $schema Optional. Field definitions, keyed by property. Default null.
	 * @return array The properties as jf2-style values, keyed by property.
	 */
	public static function rebuild_data( $data, $schema = null ) {
		$raw = array();
		foreach ( (array) $data as $key => $value ) {
			if ( ! is_string( $key ) || ! self::str_prefix( $key, 'mf2_' ) ) {
				continue;
			}
			$key = substr( $key, 4 );
			// Property names use hyphens, so an underscore marks a part: start_date.
			$pieces = explode( '_', $key, 2 );
			if ( 2 === count( $pieces ) && '' !== $pieces[0] && '' !== $pieces[1] ) {
				if ( ! isset( $raw[ $pieces[0] ] ) || ! is_array( $raw[ $pieces[0] ] ) ) {
					$raw[ $pieces[0] ] = array();
				}
				$raw[ $pieces[0] ][ $pieces[1] ] = $value;
				continue;
			}
			if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) && is_array( $value ) ) {
				$value = array_merge( $raw[ $key ], $value );
			}
			$raw[ $key ] = $value;
		}
		if ( is_array( $schema ) ) {
			return self::sanitize( $schema, $raw );
		}
		return self::assemble( $raw );
	}

	/**
	 * Reassembles date and duration parts in nested values and drops empty values.
	 *
	 * @since 4.0.2
	 *
	 * @param array $values Values.
	 * @return array
	 */
	private static function assemble( $values ) {
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( self::is_datetime_parts( $value ) ) {
					$value = self::datetime_from_parts( $value );
				} elseif ( self::is_duration_parts( $value ) ) {
					$value = self::duration_from_parts( $value );
				} else {
					$value = self::assemble( $value );
				}
			} elseif ( is_string( $value ) ) {
				$value = trim( $value );
			}
			if ( self::is_empty( $value ) ) {
				unset( $values[ $key ] );
			} else {
				$values[ $key ] = $value;
			}
		}
		return $values;
	}

	/**
	 * Sanitizes values by the type of their field definitions.
	 *
	 * This is the shared validation for every editor: form data rebuilt by
	 * rebuild_data(), or values sent by a block editor or another client.
	 * Only properties in the schema are kept, and empty or invalid values are
	 * dropped. By type:
	 * - text, textarea: sanitize_text_field() and sanitize_textarea_field().
	 * - url: sanitize_url(); unsafe URLs are dropped.
	 * - number: a number; anything else is dropped.
	 * - select: one of the option values.
	 * - list: a list of text, from an array or a semicolon-separated string.
	 * - datetime: an ISO 8601 date, from a date string or date, time and offset parts.
	 * - duration: an ISO 8601 duration, from a duration string or Y, M, D, H, I and S parts.
	 * - author: name, url and photo.
	 * - cite, venue: their nested properties. A string is read as the URL (or name).
	 * Values that are lists of the type are sanitized item by item.
	 *
	 * @since 4.0.2
	 *
	 * @param array $schema Field definitions, keyed by property.
	 * @param array $values Values, keyed by property.
	 * @return array The sanitized values, keyed by property.
	 */
	public static function sanitize( $schema, $values ) {
		$return = array();
		if ( ! is_array( $schema ) || ! is_array( $values ) ) {
			return $return;
		}
		foreach ( $schema as $key => $args ) {
			if ( ! is_array( $args ) || ! array_key_exists( $key, $values ) ) {
				continue;
			}
			$args['name'] = $key;
			if ( ! self::get( 'label', $args ) ) {
				$args['label'] = $key;
			}
			$args = self::validate( $args );
			if ( ! $args ) {
				continue;
			}
			$value = self::sanitize_field( $args, $values[ $key ] );
			if ( ! self::is_empty( $value ) ) {
				$return[ $key ] = $value;
			}
		}
		return $return;
	}

	/**
	 * Sanitizes one value by its field definition.
	 *
	 * @since 4.0.2
	 *
	 * @param array $args  Field definition, as returned by validate().
	 * @param mixed $value Value.
	 * @return mixed The sanitized value, or null if it is empty or invalid.
	 */
	private static function sanitize_field( $args, $value ) {
		$type = $args['type'];
		// A list of values of this type, such as several URLs. Lists, dates and durations have their own shapes.
		if ( is_array( $value ) && wp_is_numeric_array( $value ) && ! in_array( $type, array( 'list', 'datetime', 'duration' ), true ) ) {
			$items = array();
			foreach ( $value as $item ) {
				$item = self::sanitize_field( $args, $item );
				if ( ! self::is_empty( $item ) ) {
					$items[] = $item;
				}
			}
			if ( empty( $items ) ) {
				return null;
			}
			return 1 === count( $items ) ? $items[0] : $items;
		}
		switch ( $type ) {
			case 'cite':
			case 'venue':
			case 'author':
				$properties = 'author' === $type ? self::author_properties() : $args['properties'];
				if ( is_string( $value ) ) {
					$value = trim( $value );
					$url   = '' !== $value && wp_http_validate_url( $value ) ? sanitize_url( $value ) : '';
					if ( $url ) {
						return array_key_exists( 'url', $properties ) ? array( 'url' => $url ) : null;
					}
					return array_key_exists( 'name', $properties ) ? self::sanitize( $properties, array( 'name' => $value ) ) : null;
				}
				if ( is_array( $value ) && isset( $value['properties'] ) && function_exists( '\\ParseThis\\mf2_to_jf2' ) ) {
					$value = \ParseThis\mf2_to_jf2( $value );
				}
				return is_array( $value ) ? self::sanitize( $properties, $value ) : null;
			case 'datetime':
				if ( is_array( $value ) && self::is_datetime_parts( $value ) ) {
					return self::datetime_from_parts( self::text_parts( $value ) );
				}
				$datetime = is_string( $value ) ? kind_safe_datetime( trim( $value ) ) : kind_safe_datetime( $value );
				return $datetime ? $datetime->format( DATE_W3C ) : null;
			case 'duration':
				if ( is_array( $value ) && self::is_duration_parts( $value ) ) {
					return self::duration_from_parts( $value );
				}
				$interval = kind_safe_interval( is_string( $value ) ? trim( $value ) : $value );
				$interval = $interval ? date_interval_to_iso8601( $interval ) : '';
				return 'P' === $interval ? null : $interval;
			case 'list':
				if ( is_string( $value ) ) {
					$value = explode( ';', $value );
				}
				if ( ! is_array( $value ) ) {
					return null;
				}
				$items = array_values( array_filter( array_map( 'sanitize_text_field', array_filter( $value, 'is_scalar' ) ), 'strlen' ) );
				return $items ? $items : null;
			case 'number':
				return is_numeric( $value ) ? $value + 0 : null;
			case 'select':
				$value = is_scalar( $value ) ? (string) $value : '';
				return '' !== $value && array_key_exists( $value, $args['options'] ) ? $value : null;
			case 'url':
				$value = is_string( $value ) ? sanitize_url( trim( $value ) ) : '';
				return '' === $value ? null : $value;
			case 'textarea':
				if ( is_array( $value ) && isset( $value['value'] ) ) {
					$value = $value['value'];
				}
				$value = is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
				return '' === trim( $value ) ? null : $value;
			default:
				$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
				return '' === $value ? null : $value;
		}
	}

	/**
	 * Whether an array holds date parts: only date, time and offset keys.
	 *
	 * @since 4.0.2
	 *
	 * @param array $value Value.
	 * @return bool
	 */
	private static function is_datetime_parts( $value ) {
		return ! empty( $value ) && ! array_diff( array_keys( $value ), array( 'date', 'time', 'offset' ) ) && ( isset( $value['date'] ) || isset( $value['time'] ) );
	}

	/**
	 * Whether an array holds duration parts: only Y, M, D, H, I and S keys.
	 *
	 * @since 4.0.2
	 *
	 * @param array $value Value.
	 * @return bool
	 */
	private static function is_duration_parts( $value ) {
		return ! empty( $value ) && ! array_diff( array_keys( $value ), array( 'Y', 'M', 'D', 'H', 'I', 'S' ) );
	}

	/**
	 * Sanitizes date parts as text.
	 *
	 * @since 4.0.2
	 *
	 * @param array $parts Date parts.
	 * @return array
	 */
	private static function text_parts( $parts ) {
		return array_map(
			function ( $part ) {
				return is_scalar( $part ) ? sanitize_text_field( (string) $part ) : '';
			},
			$parts
		);
	}

	/**
	 * Builds an ISO 8601 date from date, time and offset parts.
	 *
	 * A date without a time is midnight; a time without a date is not a date.
	 * Without an offset, the site's time zone is used.
	 *
	 * @since 4.0.2
	 *
	 * @param array $parts Date parts.
	 * @return string|null The date, or null if there is none.
	 */
	private static function datetime_from_parts( $parts ) {
		$date = isset( $parts['date'] ) && is_string( $parts['date'] ) ? trim( $parts['date'] ) : '';
		$time = isset( $parts['time'] ) && is_string( $parts['time'] ) ? trim( $parts['time'] ) : '';
		if ( '' === $date ) {
			return null;
		}
		$offset   = isset( $parts['offset'] ) && is_string( $parts['offset'] ) ? trim( $parts['offset'] ) : null;
		$datetime = Kind_Time::build_datetime( $date, '' === $time ? '00:00:00' : $time, $offset );
		return $datetime ? $datetime->format( DATE_W3C ) : null;
	}

	/**
	 * Builds an ISO 8601 duration from Y, M, D, H, I (minutes) and S parts.
	 *
	 * @since 4.0.2
	 *
	 * @param array $parts Duration parts.
	 * @return string|null The duration, or null if every part is empty or zero.
	 */
	private static function duration_from_parts( $parts ) {
		$parts = array_map(
			function ( $part ) {
				return is_numeric( $part ) ? absint( $part ) : 0;
			},
			$parts
		);
		$spec  = Kind_Time::build_interval( $parts );
		return '' === $spec ? null : $spec;
	}

	/**
	 * Returns the required fields that have no value.
	 *
	 * Pass values as returned by sanitize(), so invalid values count as
	 * missing. A required cite, venue or author is filled in when it has at
	 * least one property, such as a URL or a name. Required fields nested in
	 * a cite or venue are checked only when it has a value.
	 *
	 * @since 4.0.2
	 *
	 * @param array  $schema Field definitions, keyed by property.
	 * @param array  $values Values, keyed by property.
	 * @param string $parent Optional. Path of the field these are nested in. Default empty.
	 * @return string[] Paths of the missing fields, such as 'in-reply-to' or 'in-reply-to.url'.
	 */
	public static function missing( $schema, $values, $parent = '' ) {
		$missing = array();
		if ( ! is_array( $schema ) ) {
			return $missing;
		}
		$values = is_array( $values ) ? $values : array();
		foreach ( $schema as $key => $args ) {
			if ( ! is_array( $args ) ) {
				continue;
			}
			$args['name'] = $key;
			if ( ! self::get( 'label', $args ) ) {
				$args['label'] = $key;
			}
			$args = self::validate( $args );
			if ( ! $args ) {
				continue;
			}
			$path  = '' === $parent ? (string) $key : $parent . '.' . $key;
			$value = array_key_exists( $key, $values ) ? $values[ $key ] : null;
			if ( self::is_empty( $value ) ) {
				if ( $args['required'] ) {
					$missing[] = $path;
				}
				continue;
			}
			if ( in_array( $args['type'], array( 'cite', 'venue' ), true ) && is_array( $value ) ) {
				$items = wp_is_numeric_array( $value ) ? $value : array( $value );
				foreach ( $items as $item ) {
					$missing = array_merge( $missing, self::missing( $args['properties'], $item, $path ) );
				}
			}
		}
		return array_values( array_unique( $missing ) );
	}

	/**
	 * Whether a value is empty: null, an empty or blank string, or an empty array.
	 *
	 * @since 4.0.2
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function is_empty( $value ) {
		return null === $value || ( is_string( $value ) && '' === trim( $value ) ) || ( is_array( $value ) && empty( $value ) );
	}
}
