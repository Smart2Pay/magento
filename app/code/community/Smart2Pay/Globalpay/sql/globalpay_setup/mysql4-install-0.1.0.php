<?php
    //die('Trying to setup Smart2Pay_Globalpay database');
    
    $installer = $this;
    $installer->startSetup();
    $installer->run("

        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/logger')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/logger')}` (
            `log_id` int(11) NOT NULL auto_increment,
            `log_type` varchar(255) collate utf8_unicode_ci default NULL,
            `log_message` text collate utf8_unicode_ci default NULL,
            `log_source_file` varchar(255) collate utf8_unicode_ci default NULL,
            `log_source_file_line` varchar(255) collate utf8_unicode_ci default NULL,
            `log_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (`log_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/method')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/method')}` (
            `method_id` int(11) NOT NULL auto_increment,
            `display_name` varchar(255) collate utf8_unicode_ci default NULL,
            `provider_value` varchar(255) collate utf8_unicode_ci default NULL,
            `description` text collate utf8_unicode_ci,
            `logo_url` varchar(255) collate utf8_unicode_ci default NULL,
            `guaranteed` int(1) default NULL,
            `active` int(1) default NULL,
            PRIMARY KEY  (`method_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;        
        INSERT INTO `{$installer->getTable('globalpay/method')}` (`method_id`, `display_name`, `provider_value`, `description`, `logo_url`, `guaranteed`, `active`) VALUES
        (1, 'Bank Transfer', 'banktransfer', 'Bank Transfer description', 'bank_transfer_logo_v5.gif', 1, 1),
        (2, 'iDEAL', 'ideal', 'iDEAL description', 'ideal.jpg', 1, 1),
        (3, 'MrCash', 'mrcash', 'MrCash description', 'mrcash.gif', 1, 1),
        (4, 'Giropay', 'giropay', 'Giropay description', 'giropay.gif', 1, 1),
        (5, 'EPS', 'eps', 'EPS description', 'eps-e-payment-standard.gif', 1, 1),
        (8, 'UseMyFunds', 'umb', 'UseMyFunds description', 'umb.gif', 1, 1),
        (9, 'DirectEbanking', 'dp24', 'DirectEbanking description', 'dp24_sofort.gif', 0, 1),
        (12, 'Przelewy24', 'p24', 'Przelewy24 description', 'p24.gif', 1, 1),
        (13, 'OneCard', 'onecard', 'OneCard description', 'onecard.gif', 1, 1),
        (14, 'CashU', 'cashu', 'CashU description', 'cashu.gif', 1, 1),
        (18, 'POLi', 'poli', 'POLi description', 'poli.gif', 0, 1),
        (19, 'DineroMail', 'dineromail', 'DineroMail description', 'dineromail_v2.gif', 0, 1),
        (20, 'Multibanco SIBS', 'sibs', 'Multibanco SIBS description', 'sibs_mb.gif', 1, 1),
        (22, 'Moneta Wallet', 'moneta', 'Moneta Wallet description', 'moneta_v2.gif', 1, 1),
        (23, 'WebToPay', 'webtopay', 'WebToPay description', 'webtopay_v3.gif', 1, 1),
        (24, 'Alipay', 'alipay', 'Alipay description', 'alipay.jpg', 1, 1),
        (25, 'Abaqoos', 'abaqoos', 'Abaqoos description', 'abaqoos.gif', 1, 1),
        (27, 'eBanka', 'ebanka', 'eBanka description', 'ebanka.jpg', 1, 1),
        (28, 'Ukash', 'ukash', 'Ukash description', 'ukash.gif', 1, 1),
        (29, 'GluePay', 'gluepay', 'GluePay description', 'gluepay.jpg', 1, 1),
        (32, 'Debito Banco do Brasil', 'debitobdb', 'Debito Banco do Brasil description', 'banco_do_brasil.jpg', 1, 1),
        (33, 'CuentaDigital', 'cuentadigital', 'CuentaDigital description', 'cuentadigital.gif', 1, 1),
        (34, 'CardsBrazil', 'cardsbrl', 'CardsBrazil description', 'cards_brl.gif', 0, 1),
        (35, 'PaysBuy', 'paysbuy', 'PaysBuy description', 'paysbuy.gif', 0, 1),
        (36, 'Mazooma', 'mazooma', 'Mazooma description', 'mazooma.gif', 0, 1),
        (37, 'eNETS Debit', 'enets', 'eNETS Debit description', 'enets.gif', 1, 1),
        (40, 'Paysafecard', 'paysafecard', 'Paysafecard description', 'paysafecard.gif', 1, 1),
        (42, 'PayPal', 'paypal', 'PayPal description', 'paypal.jpg', 1, 0),
        (43, 'PagTotal', 'pagtotal', 'PagTotal description', 'pagtotal.jpg', 0, 1),
        (44, 'Payeasy', 'payeasy', 'Payeasy description', 'payeasy.gif', 1, 1),
        (46, 'MercadoPago', 'mercadopago', 'MercadoPago description', 'mercadopago.jpg', 0, 1),
        (47, 'Mozca', 'mozca', 'Mozca description', 'mozca.jpg', 0, 1),
        (48, 'Gash', 'gash', 'Gash description', 'gash.gif', 1, 1),
        (49, 'ToditoCash', 'toditocash', 'ToditoCash description', 'todito_cash.gif', 1, 1),
        (52, 'SecureVaultPayments', 'svp', 'SecureVaultPayments description', 'secure_vault.gif', 1, 1),
        (1000, 'Boleto', 'paganet', 'Boleto description', 'boleto.jpg', 1, 1),
        (1001, 'Debito', 'paganet', 'Debito description', 'debito_bradesco.jpg', 1, 1),
        (1002, 'Transferencia', 'paganet', 'Transferencia description', 'bradesco_transferencia.jpg', 1, 1),
        (1003, 'QIWI Wallet', 'qiwi', 'QIWI Wallet description', 'qiwi_wallet_v2.gif', 1, 1),
        (1004, 'Beeline', 'qiwi', 'Beeline description', 'beeline.gif', 1, 1),
        (1005, 'Megafon', 'qiwi', 'Megafon description', 'megafon_v1.gif', 1, 1),
        (1006, 'MTS', 'qiwi', 'MTS description', 'mts.gif', 1, 1),
        (1007, 'WebMoney', 'moneta', 'WebMoney description', 'webmoney_v1.gif', 1, 1),
        (1008, 'Yandex', 'moneta', 'Yandex description', 'yandex_money.gif', 1, 1),
        (1009, 'Alliance Online', 'asiapay', 'Alliance Online description', 'alliance_online.gif', 1, 1),
        (1010, 'AmBank', 'asiapay', 'AmBank description', 'ambankgroup.gif', 1, 1),
        (1011, 'CIMB Clicks', 'asiapay', 'CIMB Clicks description', 'cimb_clicks.gif', 1, 1),
        (1012, 'FPX', 'asiapay', 'FPX description', 'FPX.gif', 1, 1),
        (1013, 'Hong Leong Bank Transfer', 'asiapay', 'Hong Leong Bank Transfer description', 'hong_leong.gif', 1, 1),
        (1014, 'Maybank2U', 'asiapay', 'Maybank2U description', 'maybank2u.gif', 1, 1),
        (1015, 'Meps Cash', 'asiapay', 'Meps Cash description', 'meps_cash.gif', 1, 1),
        (1016, 'Mobile Money', 'asiapay', 'Mobile Money description', 'mobile_money.gif', 1, 1),
        (1017, 'RHB', 'asiapay', 'RHB description', 'rhb.gif', 1, 1),
        (1018, 'Webcash', 'asiapay', 'Webcash description', 'web_cash.gif', 1, 1),
        (1019, 'Credit Cards Colombia', 'pagosonline', 'Credit Cards Colombia description', 'cards_colombia.jpg', 1, 1),
        (1020, 'PSE', 'pagosonline', 'PSE description', 'pse.gif', 1, 1),
        (1021, 'ACH Debit', 'pagosonline', 'ACH Debit description', 'ACH.gif', 1, 1),
        (1022, 'Via Baloto', 'pagosonline', 'Via Baloto description', 'payment_in_cash.gif', 1, 1),
        (1023, 'Referenced Payment', 'pagosonline', 'Referenced Payment description', 'payment_references.gif', 1, 1),
        (1024, 'Mandiri', 'asiapay', 'Mandiri description', 'mandiri.gif', 1, 1);

        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/country')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/country')}` (
            `country_id` int(11) NOT NULL auto_increment,
            `code` varchar(3) collate utf8_unicode_ci default NULL,
            `name` varchar(100) collate utf8_unicode_ci default NULL,
            PRIMARY KEY  (`country_id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        INSERT INTO `{$installer->getTable('globalpay/country')}` (`country_id`, `code`, `name`) VALUES
        (1, 'AD', 'Andorra'),
        (2, 'AE', 'United Arab Emirates'),
        (3, 'AF', 'Afghanistan'),
        (4, 'AG', 'Antigua and Barbuda'),
        (5, 'AI', 'Anguilla'),
        (6, 'AL', 'Albania'),
        (7, 'AM', 'Armenia'),
        (8, 'AN', 'Netherlands Antilles'),
        (9, 'AO', 'Angola'),
        (10, 'AQ', 'Antarctica'),
        (11, 'AR', 'Argentina'),
        (12, 'AS', 'American Samoa'),
        (13, 'AT', 'Austria'),
        (14, 'AU', 'Australia'),
        (15, 'AW', 'Aruba'),
        (16, 'AZ', 'Azerbaijan'),
        (17, 'BA', 'Bosnia & Herzegowina'),
        (18, 'BB', 'Barbados'),
        (19, 'BD', 'Bangladesh'),
        (20, 'BE', 'Belgium'),
        (21, 'BF', 'Burkina Faso'),
        (22, 'BG', 'Bulgaria'),
        (23, 'BH', 'Bahrain'),
        (24, 'BI', 'Burundi'),
        (25, 'BJ', 'Benin'),
        (26, 'BM', 'Bermuda'),
        (27, 'BN', 'Brunei Darussalam'),
        (28, 'BO', 'Bolivia'),
        (29, 'BR', 'Brazil'),
        (30, 'BS', 'Bahamas'),
        (31, 'BT', 'Bhutan'),
        (32, 'BV', 'Bouvet Island'),
        (33, 'BW', 'Botswana'),
        (34, 'BY', 'Belarus (formerly known as Byelorussia)'),
        (35, 'BZ', 'Belize'),
        (36, 'CA', 'Canada'),
        (37, 'CC', 'Cocos (Keeling) Islands'),
        (38, 'CD', 'Congo, Democratic Republic of the (formerly Zalre)'),
        (39, 'CF', 'Central African Republic'),
        (40, 'CG', 'Congo'),
        (41, 'CH', 'Switzerland'),
        (42, 'CI', 'Ivory Coast (Cote d''Ivoire)'),
        (43, 'CK', 'Cook Islands'),
        (44, 'CL', 'Chile'),
        (45, 'CM', 'Cameroon'),
        (46, 'CN', 'China'),
        (47, 'CO', 'Colombia'),
        (48, 'CR', 'Costa Rica'),
        (49, 'CS', 'Serbia and Montenegro (formerly Yugoslavia)'),
        (50, 'CU', 'Cuba'),
        (51, 'CV', 'Cape Verde'),
        (52, 'CX', 'Christmas Island'),
        (53, 'CY', 'Cyprus'),
        (54, 'CZ', 'Czech Republic'),
        (55, 'DE', 'Germany'),
        (56, 'DJ', 'Djibouti'),
        (57, 'DK', 'Denmark'),
        (58, 'DM', 'Dominica'),
        (59, 'DO', 'Dominican Republic'),
        (60, 'DZ', 'Algeria'),
        (61, 'EC', 'Ecuador'),
        (62, 'EE', 'Estonia'),
        (63, 'EG', 'Egypt'),
        (64, 'EH', 'Western Sahara'),
        (65, 'ER', 'Eritrea'),
        (66, 'ES', 'Spain'),
        (67, 'ET', 'Ethiopia'),
        (68, 'FI', 'Finland'),
        (69, 'FJ', 'Fiji Islands'),
        (70, 'FK', 'Falkland Islands (Malvinas)'),
        (71, 'FM', 'Micronesia, Federated States of'),
        (72, 'FO', 'Faroe Islands'),
        (73, 'FR', 'France'),
        (74, 'FX', 'France, Metropolitan'),
        (75, 'GA', 'Gabon'),
        (76, 'GB', 'United Kingdom'),
        (77, 'GD', 'Grenada'),
        (78, 'GE', 'Georgia'),
        (79, 'GF', 'French Guiana'),
        (80, 'GH', 'Ghana'),
        (81, 'GI', 'Gibraltar'),
        (82, 'GL', 'Greenland'),
        (83, 'GM', 'Gambia'),
        (84, 'GN', 'Guinea'),
        (85, 'GP', 'Guadeloupe'),
        (86, 'GQ', 'Equatorial Guinea'),
        (87, 'GR', 'Greece'),
        (88, 'GS', 'South Georgia and the South Sandwich Islands'),
        (89, 'GT', 'Guatemala'),
        (90, 'GU', 'Guam'),
        (91, 'GW', 'Guinea-Bissau'),
        (92, 'GY', 'Guyana'),
        (93, 'HK', 'Hong Kong'),
        (94, 'HM', 'Heard and McDonald Islands'),
        (95, 'HN', 'Honduras'),
        (96, 'HR', 'Croatia (local name: Hrvatska)'),
        (97, 'HT', 'Haiti'),
        (98, 'HU', 'Hungary'),
        (99, 'ID', 'Indonesia'),
        (100, 'IE', 'Ireland'),
        (101, 'IL', 'Israel'),
        (102, 'IN', 'India'),
        (103, 'IO', 'British Indian Ocean Territory'),
        (104, 'IQ', 'Iraq'),
        (105, 'IR', 'Iran, Islamic Republic of'),
        (106, 'IS', 'Iceland'),
        (107, 'IT', 'Italy'),
        (108, 'JM', 'Jamaica'),
        (109, 'JO', 'Jordan'),
        (110, 'JP', 'Japan'),
        (111, 'KE', 'Kenya'),
        (112, 'KG', 'Kyrgyzstan'),
        (113, 'KH', 'Cambodia (formerly Kampuchea)'),
        (114, 'KI', 'Kiribati'),
        (115, 'KM', 'Comoros'),
        (116, 'KN', 'Saint Kitts (Christopher) and Nevis'),
        (117, 'KP', 'Korea, Democratic People''s Republic of (North Korea)'),
        (118, 'KR', 'Korea, Republic of (South Korea)'),
        (119, 'KW', 'Kuwait'),
        (120, 'KY', 'Cayman Islands'),
        (121, 'KZ', 'Kazakhstan'),
        (122, 'LA', 'Lao People''s Democratic Republic (formerly Laos)'),
        (123, 'LB', 'Lebanon'),
        (124, 'LC', 'Saint Lucia'),
        (125, 'LI', 'Liechtenstein'),
        (126, 'LK', 'Sri Lanka'),
        (127, 'LR', 'Liberia'),
        (128, 'LS', 'Lesotho'),
        (129, 'LT', 'Lithuania'),
        (130, 'LU', 'Luxembourg'),
        (131, 'LV', 'Latvia'),
        (132, 'LY', 'Libyan Arab Jamahiriya'),
        (133, 'MA', 'Morocco'),
        (134, 'MC', 'Monaco'),
        (135, 'MD', 'Moldova, Republic of'),
        (136, 'MG', 'Madagascar'),
        (137, 'MH', 'Marshall Islands'),
        (138, 'MK', 'Macedonia, the Former Yugoslav Republic of'),
        (139, 'ML', 'Mali'),
        (140, 'MM', 'Myanmar (formerly Burma)'),
        (141, 'MN', 'Mongolia'),
        (142, 'MO', 'Macao (also spelled Macau)'),
        (143, 'MP', 'Northern Mariana Islands'),
        (144, 'MQ', 'Martinique'),
        (145, 'MR', 'Mauritania'),
        (146, 'MS', 'Montserrat'),
        (147, 'MT', 'Malta'),
        (148, 'MU', 'Mauritius'),
        (149, 'MV', 'Maldives'),
        (150, 'MW', 'Malawi'),
        (151, 'MX', 'Mexico'),
        (152, 'MY', 'Malaysia'),
        (153, 'MZ', 'Mozambique'),
        (154, 'NA', 'Namibia'),
        (155, 'NC', 'New Caledonia'),
        (156, 'NE', 'Niger'),
        (157, 'NF', 'Norfolk Island'),
        (158, 'NG', 'Nigeria'),
        (159, 'NI', 'Nicaragua'),
        (160, 'NL', 'Netherlands'),
        (161, 'NO', 'Norway'),
        (162, 'NP', 'Nepal'),
        (163, 'NR', 'Nauru'),
        (164, 'NU', 'Niue'),
        (165, 'NZ', 'New Zealand'),
        (166, 'OM', 'Oman'),
        (167, 'PA', 'Panama'),
        (168, 'PE', 'Peru'),
        (169, 'PF', 'French Polynesia'),
        (170, 'PG', 'Papua New Guinea'),
        (171, 'PH', 'Philippines'),
        (172, 'PK', 'Pakistan'),
        (173, 'PL', 'Poland'),
        (174, 'PM', 'St Pierre and Miquelon'),
        (175, 'PN', 'Pitcairn Island'),
        (176, 'PR', 'Puerto Rico'),
        (177, 'PT', 'Portugal'),
        (178, 'PW', 'Palau'),
        (179, 'PY', 'Paraguay'),
        (180, 'QA', 'Qatar'),
        (181, 'RE', 'R'),
        (182, 'RO', 'Romania'),
        (183, 'RU', 'Russian Federation'),
        (184, 'RW', 'Rwanda'),
        (185, 'SA', 'Saudi Arabia'),
        (186, 'SB', 'Solomon Islands'),
        (187, 'SC', 'Seychelles'),
        (188, 'SD', 'Sudan'),
        (189, 'SE', 'Sweden'),
        (190, 'SG', 'Singapore'),
        (191, 'SH', 'St Helena'),
        (192, 'SI', 'Slovenia'),
        (193, 'SJ', 'Svalbard and Jan Mayen Islands'),
        (194, 'SK', 'Slovakia'),
        (195, 'SL', 'Sierra Leone'),
        (196, 'SM', 'San Marino'),
        (197, 'SN', 'Senegal'),
        (198, 'SO', 'Somalia'),
        (199, 'SR', 'Suriname'),
        (200, 'ST', 'Sco Tom'),
        (201, 'SU', 'Union of Soviet Socialist Republics'),
        (202, 'SV', 'El Salvador'),
        (203, 'SY', 'Syrian Arab Republic'),
        (204, 'SZ', 'Swaziland'),
        (205, 'TC', 'Turks and Caicos Islands'),
        (206, 'TD', 'Chad'),
        (207, 'TF', 'French Southern and Antarctic Territories'),
        (208, 'TG', 'Togo'),
        (209, 'TH', 'Thailand'),
        (210, 'TJ', 'Tajikistan'),
        (211, 'TK', 'Tokelau'),
        (212, 'TM', 'Turkmenistan'),
        (213, 'TN', 'Tunisia'),
        (214, 'TO', 'Tonga'),
        (215, 'TP', 'East Timor'),
        (216, 'TR', 'Turkey'),
        (217, 'TT', 'Trinidad and Tobago'),
        (218, 'TV', 'Tuvalu'),
        (219, 'TW', 'Taiwan, Province of China'),
        (220, 'TZ', 'Tanzania, United Republic of'),
        (221, 'UA', 'Ukraine'),
        (222, 'UG', 'Uganda'),
        (223, 'UM', 'United States Minor Outlying Islands'),
        (224, 'US', 'United States of America'),
        (225, 'UY', 'Uruguay'),
        (226, 'UZ', 'Uzbekistan'),
        (227, 'VA', 'Holy See (Vatican City State)'),
        (228, 'VC', 'Saint Vincent and the Grenadines'),
        (229, 'VE', 'Venezuela'),
        (230, 'VG', 'Virgin Islands (British)'),
        (231, 'VI', 'Virgin Islands (US)'),
        (232, 'VN', 'Viet Nam'),
        (233, 'VU', 'Vanautu'),
        (234, 'WF', 'Wallis and Futuna Islands'),
        (235, 'WS', 'Samoa'),
        (236, 'XO', 'West Africa'),
        (237, 'YE', 'Yemen'),
        (238, 'YT', 'Mayotte'),
        (239, 'ZA', 'South Africa'),
        (240, 'ZM', 'Zambia'),
        (241, 'ZW', 'Zimbabwe'),
        (242, 'PS', 'Palestinian Territory');


        DROP TABLE IF EXISTS `{$installer->getTable('globalpay/countrymethod')}`;
        CREATE TABLE IF NOT EXISTS `{$installer->getTable('globalpay/countrymethod')}` (
            `id` int(11) NOT NULL auto_increment,
            `country_id` int(11) default NULL,
            `method_id` int(11) default NULL,
            `priority` int(2) default NULL,
            PRIMARY KEY  (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        INSERT INTO `{$installer->getTable('globalpay/countrymethod')}` (`id`, `country_id`, `method_id`, `priority`) VALUES
        (1, 2, 13, 1),
        (2, 2, 14, 2),
        (3, 11, 19, 1),
        (4, 11, 33, 3),
        (5, 11, 47, 2),
        (6, 13, 1, 5),
        (7, 13, 5, 1),
        (8, 13, 9, 3),
        (9, 13, 28, 4),
        (10, 13, 40, 2),
        (11, 14, 18, 1),
        (12, 14, 28, 2),
        (13, 20, 1, 3),
        (14, 20, 3, 1),
        (15, 20, 28, 4),
        (16, 20, 40, 2),
        (17, 22, 1, 1),
        (18, 23, 13, 1),
        (19, 23, 14, 2),
        (20, 29, 32, 2),
        (21, 29, 34, 3),
        (22, 29, 43, 7),
        (23, 29, 46, 1),
        (24, 29, 47, 8),
        (25, 29, 1000, 4),
        (26, 29, 1001, 5),
        (27, 29, 1002, 6),
        (28, 36, 8, 1),
        (29, 36, 28, 2),
        (30, 41, 1, 1),
        (31, 41, 9, 2),
        (32, 44, 19, 2),
        (33, 44, 47, 3),
        (34, 46, 24, 1),
        (35, 46, 28, 2),
        (36, 47, 19, 7),
        (37, 47, 47, 8),
        (38, 47, 1019, 1),
        (39, 47, 1020, 2),
        (40, 47, 1021, 3),
        (41, 47, 1022, 4),
        (42, 47, 1023, 5),
        (43, 53, 13, 2),
        (44, 53, 14, 3),
        (45, 53, 28, 1),
        (46, 54, 1, 3),
        (47, 54, 27, 1),
        (48, 54, 28, 2),
        (49, 55, 1, 6),
        (50, 55, 4, 1),
        (51, 55, 9, 2),
        (52, 55, 14, 5),
        (53, 55, 28, 4),
        (54, 55, 40, 3),
        (55, 57, 1, 2),
        (56, 57, 28, 3),
        (57, 57, 29, 1),
        (58, 60, 14, 1),
        (59, 62, 1, 4),
        (60, 62, 23, 1),
        (61, 62, 28, 3),
        (62, 62, 29, 2),
        (63, 63, 13, 1),
        (64, 63, 14, 2),
        (65, 66, 1, 2),
        (66, 66, 14, 4),
        (67, 66, 28, 3),
        (68, 66, 29, 1),
        (69, 68, 1, 1),
        (70, 68, 28, 3),
        (71, 68, 29, 2),
        (72, 73, 1, 1),
        (73, 73, 14, 2),
        (74, 76, 1, 1),
        (75, 76, 8, 4),
        (76, 76, 9, 3),
        (77, 76, 14, 5),
        (78, 76, 28, 2),
        (79, 87, 28, 1),
        (80, 93, 48, 1),
        (81, 98, 1, 3),
        (82, 98, 25, 1),
        (83, 98, 28, 2),
        (84, 98, 40, 4),
        (85, 99, 1024, 1),
        (86, 100, 1, 2),
        (87, 100, 14, 3),
        (88, 100, 28, 1),
        (89, 101, 13, 1),
        (90, 101, 14, 2),
        (91, 104, 13, 1),
        (92, 104, 14, 2),
        (93, 107, 1, 2),
        (94, 107, 14, 3),
        (95, 107, 28, 1),
        (96, 109, 13, 1),
        (97, 109, 14, 2),
        (98, 119, 13, 1),
        (99, 121, 1003, 1),
        (100, 121, 1004, 2),
        (101, 121, 1005, 3),
        (102, 121, 1006, 4),
        (103, 123, 13, 1),
        (104, 123, 14, 2),
        (105, 129, 1, 3),
        (106, 129, 23, 1),
        (107, 129, 29, 2),
        (108, 130, 1, 1),
        (109, 131, 23, 1),
        (110, 131, 28, 3),
        (111, 131, 29, 2),
        (112, 151, 19, 2),
        (113, 151, 28, 4),
        (114, 151, 49, 1),
        (115, 152, 1009, 1),
        (116, 152, 1010, 2),
        (117, 152, 1011, 3),
        (118, 152, 1012, 4),
        (119, 152, 1013, 5),
        (120, 152, 1014, 6),
        (121, 152, 1015, 7),
        (122, 152, 1016, 8),
        (123, 152, 1017, 9),
        (124, 152, 1018, 10),
        (125, 158, 14, 1),
        (126, 160, 1, 5),
        (127, 160, 2, 1),
        (128, 160, 9, 2),
        (129, 160, 28, 4),
        (130, 160, 40, 3),
        (131, 161, 1, 1),
        (132, 161, 28, 3),
        (133, 161, 29, 2),
        (134, 165, 18, 2),
        (135, 165, 28, 2),
        (136, 166, 13, 1),
        (137, 166, 14, 2),
        (138, 171, 44, 1),
        (139, 173, 1, 2),
        (140, 173, 12, 1),
        (141, 173, 14, 4),
        (142, 173, 28, 3),
        (143, 173, 40, 5),
        (144, 177, 1, 3),
        (145, 177, 14, 4),
        (146, 177, 20, 1),
        (147, 177, 28, 2),
        (148, 180, 13, 1),
        (149, 180, 14, 2),
        (150, 182, 1, 2),
        (151, 182, 40, 1),
        (152, 183, 22, 1),
        (153, 183, 28, 8),
        (154, 183, 1003, 4),
        (155, 183, 1004, 5),
        (156, 183, 1005, 6),
        (157, 183, 1006, 7),
        (158, 183, 1007, 2),
        (159, 183, 1008, 3),
        (160, 185, 13, 1),
        (161, 185, 14, 2),
        (162, 188, 14, 1),
        (163, 189, 1, 2),
        (164, 189, 28, 3),
        (165, 189, 29, 1),
        (166, 190, 37, 1),
        (167, 192, 14, 2),
        (168, 192, 28, 1),
        (169, 194, 1, 1),
        (170, 209, 35, 1),
        (171, 213, 13, 1),
        (172, 213, 14, 2),
        (173, 216, 1, 1),
        (174, 216, 13, 3),
        (175, 216, 14, 4),
        (176, 216, 28, 5),
        (177, 216, 40, 2),
        (178, 219, 48, 1),
        (179, 221, 22, 1),
        (180, 221, 28, 4),
        (181, 221, 1007, 2),
        (182, 221, 1008, 3),
        (183, 224, 8, 3),
        (184, 224, 36, 1),
        (185, 224, 52, 2),
        (186, 239, 1, 2),
        (187, 239, 28, 1),
        (188, 242, 13, 1),
        (189, 242, 14, 2);

    ");
    $installer->endSetup();
?>