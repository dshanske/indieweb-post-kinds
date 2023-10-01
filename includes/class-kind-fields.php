<?php
/**
 * Kind Fields Class
 *
 * @package Post Kind
 * Used to Generate Form Fields for a Post UI
 *
 * Fields currently supported and their options{
	* datetime
	* duration
	* author - is a representation of an author and consists of name, url, and photo by default
	* select
		* options - associative array of values for the select. Key being the value and the value being the description
	* url
	* text
	* list
	* textarea
	* number
	* cite - is a representation of an h-cite object
* }
 */

class Kind_Fields {

	public static function timezone_list() {
		return array(
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
			'America/Godthab'                => __( '(GMT-03:00) Godthab', 'indieweb-post-kinds' ),
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
			'Europe/Kiev'                    => __( '(GMT+02:00) Kiev', 'indieweb-post-kinds' ),
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
			'Asia/Calcutta'                  => __( '(GMT+05:30) India Standard Time', 'indieweb-post-kinds' ),
			'Asia/Colombo'                   => __( '(GMT+05:30) Colombo', 'indieweb-post-kinds' ),
			'Asia/Katmandu'                  => __( '(GMT+05:45) Katmandu', 'indieweb-post-kinds' ),
			'Antarctica/Vostok'              => __( '(GMT+06:00) Vostok', 'indieweb-post-kinds' ),
			'Asia/Almaty'                    => __( '(GMT+06:00) Almaty', 'indieweb-post-kinds' ),
			'Asia/Bishkek'                   => __( '(GMT+06:00) Bishkek', 'indieweb-post-kinds' ),
			'Asia/Dhaka'                     => __( '(GMT+06:00) Dhaka', 'indieweb-post-kinds' ),
			'Asia/Omsk'                      => __( '(GMT+06:00) Moscow+03 - Omsk, Novosibirsk', 'indieweb-post-kinds' ),
			'Asia/Thimphu'                   => __( '(GMT+06:00) Thimphu', 'indieweb-post-kinds' ),
			'Indian/Chagos'                  => __( '(GMT+06:00) Chagos', 'indieweb-post-kinds' ),
			'Asia/Rangoon'                   => __( '(GMT+06:30) Rangoon', 'indieweb-post-kinds' ),
			'Indian/Cocos'                   => __( '(GMT+06:30) Cocos', 'indieweb-post-kinds' ),
			'Antarctica/Davis'               => __( '(GMT+07:00) Davis', 'indieweb-post-kinds' ),
			'Asia/Bangkok'                   => __( '(GMT+07:00) Bangkok', 'indieweb-post-kinds' ),
			'Asia/Hovd'                      => __( '(GMT+07:00) Hovd', 'indieweb-post-kinds' ),
			'Asia/Jakarta'                   => __( '(GMT+07:00) Jakarta', 'indieweb-post-kinds' ),
			'Asia/Krasnoyarsk'               => __( '(GMT+07:00) Moscow+04 - Krasnoyarsk', 'indieweb-post-kinds' ),
			'Asia/Saigon'                    => __( '(GMT+07:00) Hanoi', 'indieweb-post-kinds' ),
			'Asia/Ho_Chi_Minh'               => __( '(GMT+07:00) Ho Chi Minh', 'indieweb-post-kinds' ),
			'Indian/Christmas'               => __( '(GMT+07:00) Christmas', 'indieweb-post-kinds' ),
			'Antarctica/Casey'               => __( '(GMT+08:00) Casey', 'indieweb-post-kinds' ),
			'Asia/Brunei'                    => __( '(GMT+08:00) Brunei', 'indieweb-post-kinds' ),
			'Asia/Choibalsan'                => __( '(GMT+08:00) Choibalsan', 'indieweb-post-kinds' ),
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
			'Pacific/Enderbury'              => __( '(GMT+13:00) Enderbury', 'indieweb-post-kinds' ),
			'Pacific/Fakaofo'                => __( '(GMT+13:00) Fakaofo', 'indieweb-post-kinds' ),
			'Pacific/Tongatapu'              => __( '(GMT+13:00) Tongatapu', 'indieweb-post-kinds' ),
			'Pacific/Kiritimati'             => __( '(GMT+14:00) Kiritimati', 'indieweb-post-kinds' ),
		);
	}

	public static function get_offset_list() {
		$o = wp_cache_get( 'kind_offset_list' );
		if ( false !== $o ) {
			return $o;
		}
		$o       = array();
		$t_zones = timezone_identifiers_list();
		foreach ( $t_zones as $a ) {
			$datetime = new DateTime( 'now', new DateTimeZone( $a ) );
			$o[]      = get_datetime_offset( $datetime );
		}
		$o = array_unique( $o );
		asort( $o );
		wp_cache_set( 'kind_offset_list', $o, '', DAY_IN_SECONDS );
		return $o;
	}

	/**
	 * Function to render date/time field inputs.
	 *
	 * @access public
	 *
	 * @param string|DateTime $time   Date/time value.
	 * @param string $name Property name
	 * @param array $args
	 * @param string|array $class  Class to use for fields.
	 * @return string
	 */
	public static function field_datetime( $args, $datetime ) {
		if ( is_string( $datetime ) ) {
			$datetime = new DateTime( $datetime, wp_timezone() );
		}
		if ( ! $datetime ) {
			$datetime = new DateTime( 'now', wp_timezone() );
		}
		$return   = array();
		$time     = divide_datetime( $datetime );
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<input type="date" name="mf2_%1$s_date" id="mf2_%1$s_date" value="%2$s"/>', $args['name'], ( $time['date'] ?? '' ) );
		$return[] = sprintf( '<input type="time" name="mf2_%1$s_time" id="mf2_%1$s_time" step="1" value="%2$s"/>', $args['name'], ( $time['time'] ?? '' ) );
		$return[] = sprintf( '<select name="%1s_offset" id="%1$s_offset">', $args['name'] );
		foreach ( self::get_offset_list() as $offset ) {
			$return[] = sprintf( '<option value="%1$s"%2$s>%3$s</option>', $offset, selected( $offset, $time['offset'], false ), sprintf( 'GMT%1$s', $offset ) );
		}
		$return[] = '</select></label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render dateinterval field inputs.
	 *
	 * @access public
	 *
	 * @param string|Kind_DateTime $time   Date/time value.
	 * @param string $name Property name
	 * @param array $args
	 * @param string|array $class  Class to use for fields.
	 * @return string
	 */
	public static function field_duration( $args, $interval ) {
		if ( is_string( $interval ) ) {
			$interval = new DateInterval( $interval );
		}
		if ( ! $interval ) {
			$interval = new DateInterval( 'PT0S' );
		}
		$return   = array();
		$duration = divide_interval( $interval );
		$max      = array(
			'Y' => 1000,
			'M' => 11,
			'D' => 31,
			'H' => 23,
			'I' => 59,
			'S' => 60,
		);
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		foreach ( $args['pieces'] as $piece ) {
			$return[] = sprintf( '<input type="number" name="mf2_%2$s_%1$s" id="mf2_%3$s_%1$s" value="%4$s" step="1" max="%5$s" />', $piece, $args['name'], $args['id'], ( $duration['year'] ?? '' ), $max[ $piece ] );
		}
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render author inputs.
	 *
	 * @access public
	 *
	 * @param string $prefix Field prefix.
	 * @param string|array $author Defautl values. If string, considered to be the name property below. {
		 * @param string $name Author Name
		 * @param string $url Author URL
		 * @param string $photo Author Photo
	 * }
	 * @return string
	 */
	public static function field_author( $args, $author ) {
		if ( ! $author ) {
			$author = array();
		}
		$props = array(
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
		// Ensure all props are set for values
		foreach ( array_keys( $props ) as $prop ) {
			if ( ! array_key_exists( $prop, $author ) ) {
				$author[ $prop ] = '';
			}
		}
		return self::render( $props, $author );
	}


	/**
	 * Function to render a select.
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param string $selected Selected field type.
	 * @return string
	 */
	public static function field_select( $args, $selected ) {
		$return = array();

		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<select name="mf2_%1s" id="mf2_%1$s">', $args['name'] );
		foreach ( $args['options'] as $key => $value ) {
			$return[] = sprintf( '<option value="%1$s"%2$s>%3$s</option>', $key, selected( $key, $selected, false ), $value );
		}
		$return[] = '</select></label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render a url.
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param string $url
	 * @return string
	 */
	public static function field_url( $args, $url ) {
		$return   = array();
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<input name="mf2_%1s" id="mf2_%1$s" type="url" value="%2$s">', $args['name'], $url );
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render a number.
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public static function field_number( $args, $value ) {
		$return   = array();
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<input name="mf2_%1s" id="mf2_%1$s" type="number" step="%2$s" value="%3$s">', $args['name'], $args['step'], $value );
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render a text.
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public static function field_text( $args, $value ) {
		$return   = array();
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<input name="mf2_%1s" id="mf2_%1$s" type="text" value="%2$s">', $args['name'], $value );
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	/**
	 * Function to render a textarea.
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public static function field_textarea( $args, $value ) {
		$return   = array();
		$return[] = sprintf( '<label for="mf2_%1$s" class="%2$s">%3$s', $args['name'], $args['class'], $args['label'] );
		$return[] = sprintf( '<textarea name="mf2_%1s" id="mf2_%1$s">%2$s</textarea>', $args['name'], $value );
		$return[] = '</label>';
		return implode( PHP_EOL, $return );
	}

	public static function field_list( $args, $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ';', $value );
		}
		return self::field_textarea( $args, $value );
	}

	public static function field_venue( $args, $value ) {
		return self::render( $args['properties'], $value );
	}

	public static function field_cite( $args, $value ) {
		return self::render( $args['properties'], $value );
	}

	public static function str_prefix( $source, $prefix ) {
		return strncmp( $source, $prefix, strlen( $prefix ) ) === 0;
	}

	private static function get( $key, $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}
		return false;
	}

	private static function validate( $element ) {
		// Everything must have a type
		if ( ! self::get( 'type', $element ) ) {
			return false;
		}
		// Everything must have a label
		if ( ! self::get( 'label', $element ) ) {
			return false;
		}
		// If no name property then the label property copies over
		if ( ! self::get( 'name', $element ) ) {
			$element['name'] = sanitize_title( $element['label'] );
		}
		// If no label property then the name property copies over
		if ( ! self::get( 'label', $element ) ) {
			$element['label'] = $element['name'];
		}
		// If no class property then the class property is set to empty
		if ( ! self::get( 'class', $element ) ) {
			$element['class'] = '';
		}
		// Class can be an array
		if ( is_array( $element['class'] ) ) {
			$element['class'] = implode( ' ', $element['class'] );
		}
		$type = self::get( 'type', $element );
		// Any non supported type should be considered to be text
		if ( ! self::supported_type( $type ) ) {
			$type = 'text';
		}
		// Type Specific Conditions
		switch ( $type ) {
			case 'cite':
			case 'venue':
				if ( ! array_key_exists( 'properties', $element ) ) {
					return false;
				}
				break;
			case 'number':
				if ( ! array_key_exists( 'step', $element ) ) {
					$element['step'] = 1; // Defaults to Even Numbers
				}
				break;
			case 'select':
				if ( ! array_key_exists( 'options', $elements ) ) {
					return false;
				}
				break;
			case 'duration':
				if ( array_key_exists( 'pieces', $elements ) ) {
					// Ensure only valid options
					$element['pieces'] = array_intersect( array( 'Y', 'M', 'D', 'H', 'I', 'S' ), $elements['pieces'] );
				} else {
					// By default only show hours, minutes, seconds
					$element['pieces'] = array( 'H', 'I', 'S' );
				}
				break;
		}
		return $element;
	}
	public static function supported_type( $type ) {
		return in_array( $type, array( 'cite', 'venue', 'coordinate', 'author', 'datetime', 'duration', 'text', 'url', 'textarea', 'list', 'section' ), true );
	}


	/**
	 * Sets an array with only the mf2 prefixed meta.
	 *
	 */
	private function get_mf2meta( $post ) {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		$meta = get_post_meta( $post->ID );
		if ( ! $meta ) {
			return array();
		}
		foreach ( $meta as $key => $value ) {
			if ( ! self::str_prefix( $key, 'mf2_' ) ) {
				unset( $meta[ $key ] );
			} else {
				unset( $meta[ $key ] );
				$key = str_replace( 'mf2_', '', $key );
				// Do not save microput prefixed instructions
				if ( self::str_prefix( $key, 'mp-' ) ) {
					continue;
				}
				$value = array_map( 'maybe_unserialize', $value );
				if ( 1 === count( $value ) ) {
					$value = array_shift( $value );
				}
				if ( is_string( $value ) ) {
					$meta[ $key ] = array( $value );
				} else {
					$meta[ $key ] = $value;
				}
			}
		}
		return array_filter( $meta );
	}

	/**
	 * Function to render a form from a schema array
	 *
	 * @access public
	 *
	 * @param array $schema
	 *
	 * @return string Form
	 */
	public static function render( $schema, $values = array() ) {
		$return = array();
		foreach ( $schema as $key => $args ) {
			$args['name'] = sanitize_title( $key );
			$args         = self::validate( $args );
			if ( ! $args ) {
				break;
			}
			$type = self::get( 'type', $args );
			if ( $type ) {
				$return[] = call_user_func( array( get_called_class(), 'field_' . $type ), $args, self::get( $key, $values ) );
			}
		}
		return implode( '<br />', $return );
	}

	/* Extracts microformats elements from post data.
	 * Microformats elements are prefixed by mf2_
	 * After that, underscore would indicate properties that need to be reconstituted
	*/
	public static function rebuild_data( $data ) {
		$raw = array();
		foreach ( $data as $key => $value ) {
			if ( self::str_prefix( $key, 'mf2_' ) ) {
				$key         = str_replace( 'mf2_', '', $key );
				$raw[ $key ] = $value;
			}
		}
		foreach ( $raw as $key => $value ) {
			$pieces = explode( '_', $key );
			if ( 2 === count( $pieces ) ) {
				if ( ! array_key_exists( $pieces[0], $raw ) ) {
					$raw[ $pieces[0] ] = array();
				}
				$raw[ $pieces[0] ] = $pieces[1];
				unset( $raw[ $key ] );
				// If this has the elements of a duration
				if ( ! empty( array_intersect( array( 'Y', 'M', 'D', 'H', 'I', 'S' ), $raw[ $pieces[0] ] ) ) ) {
					$interval          = build_interval( $raw[ $pieces[0] ] );
					$raw[ $pieces[0] ] = date_interval_to_iso8601( $interval );
				}
				// If this has the elements of a datetime
				if ( ! empty( array_intersect( array( 'date', 'time', 'offset' ), $raw[ $pieces[0] ] ) ) ) {
					$datetime          = build_datetime( $raw[ $pieces[0] ] );
					$raw[ $pieces[0] ] = $datetime->format( DATE_W3C );
				}
			}
		}
		return array_filter( $raw );
	}
}
