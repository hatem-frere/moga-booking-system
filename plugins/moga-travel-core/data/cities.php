<?php
/**
 * Cities Data Library — Worldwide Coverage
 *
 * Comprehensive cities database for all countries.
 * Used as the primary city data source for:
 *   - Admin meta box city dropdown (automatic, no manual setup)
 *   - Search forms city autocomplete
 *   - Location filtering on search results page
 *   - Auto-sync to moga_location taxonomy on property/tour save
 *
 * Flow:
 *   Admin selects country → city dropdown populates automatically
 *   from this file → on save, taxonomy terms created automatically
 *   in background → no manual taxonomy management needed.
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
 * Get all cities organized by country ISO code.
 *
 * @since  1.0.0
 * @return array Cities organized by country code.
 */
function moga_get_all_cities() {
    return array(

        // ============================================================
        // ARAB COUNTRIES
        // ============================================================

        'EG' => array(
            array( 'name' => 'Cairo',           'lat' => '30.0444',  'lng' => '31.2357',  'popular' => true  ),
            array( 'name' => 'Alexandria',      'lat' => '31.2001',  'lng' => '29.9187',  'popular' => true  ),
            array( 'name' => 'Hurghada',        'lat' => '27.2579',  'lng' => '33.8116',  'popular' => true  ),
            array( 'name' => 'Sharm El Sheikh', 'lat' => '27.9158',  'lng' => '34.3300',  'popular' => true  ),
            array( 'name' => 'Luxor',           'lat' => '25.6872',  'lng' => '32.6396',  'popular' => true  ),
            array( 'name' => 'Aswan',           'lat' => '24.0889',  'lng' => '32.8998',  'popular' => true  ),
            array( 'name' => 'Dahab',           'lat' => '28.4833',  'lng' => '34.5167',  'popular' => true  ),
            array( 'name' => 'El Gouna',        'lat' => '27.3950',  'lng' => '33.6783',  'popular' => true  ),
            array( 'name' => 'Sahl Hasheesh',   'lat' => '27.1167',  'lng' => '33.8833',  'popular' => true  ),
            array( 'name' => 'Marsa Alam',      'lat' => '25.0667',  'lng' => '34.8833',  'popular' => false ),
            array( 'name' => 'Ain Sokhna',      'lat' => '29.5934',  'lng' => '32.3455',  'popular' => false ),
            array( 'name' => 'North Coast',     'lat' => '30.9197',  'lng' => '29.5516',  'popular' => false ),
            array( 'name' => 'Marsa Matrouh',   'lat' => '31.3543',  'lng' => '27.2373',  'popular' => false ),
            array( 'name' => 'Siwa Oasis',      'lat' => '29.2031',  'lng' => '25.5196',  'popular' => false ),
            array( 'name' => 'Port Said',       'lat' => '31.2653',  'lng' => '32.3019',  'popular' => false ),
            array( 'name' => 'Ismailia',        'lat' => '30.5965',  'lng' => '32.2715',  'popular' => false ),
            array( 'name' => 'Suez',            'lat' => '29.9668',  'lng' => '32.5498',  'popular' => false ),
            array( 'name' => 'Tanta',           'lat' => '30.7865',  'lng' => '31.0004',  'popular' => false ),
            array( 'name' => 'Mansoura',        'lat' => '31.0364',  'lng' => '31.3807',  'popular' => false ),
            array( 'name' => 'Minya',           'lat' => '28.0871',  'lng' => '30.7618',  'popular' => false ),
            array( 'name' => 'Sohag',           'lat' => '26.5590',  'lng' => '31.6957',  'popular' => false ),
            array( 'name' => 'Qena',            'lat' => '26.1615',  'lng' => '32.7183',  'popular' => false ),
            array( 'name' => 'Taba',            'lat' => '29.4972',  'lng' => '34.8986',  'popular' => false ),
            array( 'name' => 'Nuweiba',         'lat' => '28.9667',  'lng' => '34.6667',  'popular' => false ),
            array( 'name' => 'Saint Catherine', 'lat' => '28.5560',  'lng' => '33.9760',  'popular' => false ),
            array( 'name' => 'Abu Simbel',      'lat' => '22.3372',  'lng' => '31.6258',  'popular' => false ),
            array( 'name' => 'Makadi Bay',      'lat' => '27.0333',  'lng' => '33.9167',  'popular' => false ),
            array( 'name' => 'Faiyum',          'lat' => '29.3084',  'lng' => '30.8428',  'popular' => false ),
            array( 'name' => 'Zagazig',         'lat' => '30.5877',  'lng' => '31.5021',  'popular' => false ),
            array( 'name' => 'Beni Suef',       'lat' => '29.0661',  'lng' => '31.0994',  'popular' => false ),
            array( 'name' => 'Giza',             'lat' => '30.0131',  'lng' => '31.2089',  'popular' => true  ),
            array( 'name' => 'Asyut',            'lat' => '27.1809',  'lng' => '31.1837',  'popular' => false ),
            array( 'name' => 'Kafr El Sheikh',   'lat' => '31.1107',  'lng' => '30.9388',  'popular' => false ),
            array( 'name' => 'Damanhur',         'lat' => '31.0341',  'lng' => '30.4685',  'popular' => false ),
            array( 'name' => 'Wadi El Natrun',   'lat' => '30.3667',  'lng' => '30.3167',  'popular' => false ),
            array( 'name' => 'El Alamein',       'lat' => '30.8333',  'lng' => '28.9500',  'popular' => false ),
            array( 'name' => 'Rashid',           'lat' => '31.4000',  'lng' => '30.4167',  'popular' => false ),
            array( 'name' => 'Baltim',           'lat' => '31.5581',  'lng' => '31.0864',  'popular' => false ),
            array( 'name' => 'New Cairo',        'lat' => '30.0300',  'lng' => '31.4700',  'popular' => false ),
            array( 'name' => 'New Administrative Capital', 'lat' => '30.0167', 'lng' => '31.7500', 'popular' => false ),
        ),
        // )

        'SA' => array(
            array( 'name' => 'Riyadh',          'lat' => '24.7136',  'lng' => '46.6753',  'popular' => true  ),
            array( 'name' => 'Jeddah',          'lat' => '21.3891',  'lng' => '39.8579',  'popular' => true  ),
            array( 'name' => 'Mecca',           'lat' => '21.4225',  'lng' => '39.8262',  'popular' => true  ),
            array( 'name' => 'Medina',          'lat' => '24.5247',  'lng' => '39.5692',  'popular' => true  ),
            array( 'name' => 'Dammam',          'lat' => '26.4207',  'lng' => '50.0888',  'popular' => false ),
            array( 'name' => 'Khobar',          'lat' => '26.2172',  'lng' => '50.1971',  'popular' => false ),
            array( 'name' => 'Tabuk',           'lat' => '28.3838',  'lng' => '36.5550',  'popular' => false ),
            array( 'name' => 'Abha',            'lat' => '18.2164',  'lng' => '42.5053',  'popular' => false ),
            array( 'name' => 'AlUla',           'lat' => '26.6208',  'lng' => '37.9218',  'popular' => true  ),
            array( 'name' => 'Neom',            'lat' => '28.0000',  'lng' => '35.2000',  'popular' => false ),
        ),

        'AE' => array(
            array( 'name' => 'Dubai',           'lat' => '25.2048',  'lng' => '55.2708',  'popular' => true  ),
            array( 'name' => 'Abu Dhabi',       'lat' => '24.4539',  'lng' => '54.3773',  'popular' => true  ),
            array( 'name' => 'Sharjah',         'lat' => '25.3463',  'lng' => '55.4209',  'popular' => false ),
            array( 'name' => 'Ajman',           'lat' => '25.4052',  'lng' => '55.5136',  'popular' => false ),
            array( 'name' => 'Ras Al Khaimah',  'lat' => '25.7895',  'lng' => '55.9432',  'popular' => false ),
            array( 'name' => 'Fujairah',        'lat' => '25.1288',  'lng' => '56.3265',  'popular' => false ),
        ),

        'KW' => array(
            array( 'name' => 'Kuwait City',     'lat' => '29.3759',  'lng' => '47.9774',  'popular' => true  ),
            array( 'name' => 'Salmiya',         'lat' => '29.3370',  'lng' => '48.0760',  'popular' => false ),
            array( 'name' => 'Hawalli',         'lat' => '29.3322',  'lng' => '48.0317',  'popular' => false ),
            array( 'name' => 'Jahra',           'lat' => '29.3375',  'lng' => '47.6581',  'popular' => false ),
        ),

        'QA' => array(
            array( 'name' => 'Doha',            'lat' => '25.2854',  'lng' => '51.5310',  'popular' => true  ),
            array( 'name' => 'Al Wakrah',       'lat' => '25.1664',  'lng' => '51.6010',  'popular' => false ),
            array( 'name' => 'Al Rayyan',       'lat' => '25.2919',  'lng' => '51.4244',  'popular' => false ),
            array( 'name' => 'Lusail',          'lat' => '25.4167',  'lng' => '51.5000',  'popular' => false ),
        ),

        'BH' => array(
            array( 'name' => 'Manama',          'lat' => '26.2235',  'lng' => '50.5876',  'popular' => true  ),
            array( 'name' => 'Riffa',           'lat' => '26.1300',  'lng' => '50.5550',  'popular' => false ),
            array( 'name' => 'Muharraq',        'lat' => '26.2572',  'lng' => '50.6128',  'popular' => false ),
        ),

        'OM' => array(
            array( 'name' => 'Muscat',          'lat' => '23.5880',  'lng' => '58.3829',  'popular' => true  ),
            array( 'name' => 'Salalah',         'lat' => '17.0151',  'lng' => '54.0924',  'popular' => true  ),
            array( 'name' => 'Nizwa',           'lat' => '22.9333',  'lng' => '57.5333',  'popular' => false ),
            array( 'name' => 'Sur',             'lat' => '22.5667',  'lng' => '59.5289',  'popular' => false ),
        ),

        'JO' => array(
            array( 'name' => 'Amman',           'lat' => '31.9454',  'lng' => '35.9284',  'popular' => true  ),
            array( 'name' => 'Aqaba',           'lat' => '29.5269',  'lng' => '35.0060',  'popular' => true  ),
            array( 'name' => 'Petra',           'lat' => '30.3285',  'lng' => '35.4444',  'popular' => true  ),
            array( 'name' => 'Jerash',          'lat' => '32.2742',  'lng' => '35.8997',  'popular' => false ),
            array( 'name' => 'Irbid',           'lat' => '32.5556',  'lng' => '35.8500',  'popular' => false ),
        ),

        'LB' => array(
            array( 'name' => 'Beirut',          'lat' => '33.8938',  'lng' => '35.5018',  'popular' => true  ),
            array( 'name' => 'Tripoli',         'lat' => '34.4367',  'lng' => '35.8497',  'popular' => false ),
            array( 'name' => 'Sidon',           'lat' => '33.5610',  'lng' => '35.3689',  'popular' => false ),
            array( 'name' => 'Byblos',          'lat' => '34.1236',  'lng' => '35.6517',  'popular' => false ),
        ),

        'SY' => array(
            array( 'name' => 'Damascus',        'lat' => '33.5138',  'lng' => '36.2765',  'popular' => true  ),
            array( 'name' => 'Aleppo',          'lat' => '36.2021',  'lng' => '37.1343',  'popular' => false ),
            array( 'name' => 'Latakia',         'lat' => '35.5317',  'lng' => '35.7912',  'popular' => false ),
        ),

        'IQ' => array(
            array( 'name' => 'Baghdad',         'lat' => '33.3152',  'lng' => '44.3661',  'popular' => true  ),
            array( 'name' => 'Basra',           'lat' => '30.5085',  'lng' => '47.7804',  'popular' => false ),
            array( 'name' => 'Erbil',           'lat' => '36.1901',  'lng' => '44.0091',  'popular' => false ),
            array( 'name' => 'Najaf',           'lat' => '31.9936',  'lng' => '44.3350',  'popular' => false ),
        ),

        'YE' => array(
            array( 'name' => 'Sanaa',           'lat' => '15.3694',  'lng' => '44.1910',  'popular' => true  ),
            array( 'name' => 'Aden',            'lat' => '12.7797',  'lng' => '45.0095',  'popular' => false ),
            array( 'name' => 'Taiz',            'lat' => '13.5789',  'lng' => '44.0209',  'popular' => false ),
        ),

        'LY' => array(
            array( 'name' => 'Tripoli',         'lat' => '32.9022',  'lng' => '13.1800',  'popular' => true  ),
            array( 'name' => 'Benghazi',        'lat' => '32.1154',  'lng' => '20.0868',  'popular' => false ),
            array( 'name' => 'Misrata',         'lat' => '32.3754',  'lng' => '15.0925',  'popular' => false ),
        ),

        'TN' => array(
            array( 'name' => 'Tunis',           'lat' => '36.8065',  'lng' => '10.1815',  'popular' => true  ),
            array( 'name' => 'Sousse',          'lat' => '35.8245',  'lng' => '10.6346',  'popular' => true  ),
            array( 'name' => 'Djerba',          'lat' => '33.8076',  'lng' => '10.8451',  'popular' => true  ),
            array( 'name' => 'Sfax',            'lat' => '34.7400',  'lng' => '10.7600',  'popular' => false ),
            array( 'name' => 'Monastir',        'lat' => '35.7643',  'lng' => '10.8113',  'popular' => false ),
        ),

        'DZ' => array(
            array( 'name' => 'Algiers',         'lat' => '36.7372',  'lng' => '3.0865',   'popular' => true  ),
            array( 'name' => 'Oran',            'lat' => '35.6969',  'lng' => '-0.6331',  'popular' => false ),
            array( 'name' => 'Constantine',     'lat' => '36.3650',  'lng' => '6.6147',   'popular' => false ),
            array( 'name' => 'Annaba',          'lat' => '36.9000',  'lng' => '7.7667',   'popular' => false ),
        ),

        'MA' => array(
            array( 'name' => 'Casablanca',      'lat' => '33.5731',  'lng' => '-7.5898',  'popular' => true  ),
            array( 'name' => 'Marrakech',       'lat' => '31.6295',  'lng' => '-7.9811',  'popular' => true  ),
            array( 'name' => 'Fez',             'lat' => '34.0181',  'lng' => '-5.0078',  'popular' => true  ),
            array( 'name' => 'Rabat',           'lat' => '33.9716',  'lng' => '-6.8498',  'popular' => false ),
            array( 'name' => 'Tangier',         'lat' => '35.7595',  'lng' => '-5.8340',  'popular' => false ),
            array( 'name' => 'Agadir',          'lat' => '30.4278',  'lng' => '-9.5981',  'popular' => true  ),
            array( 'name' => 'Meknes',          'lat' => '33.8935',  'lng' => '-5.5473',  'popular' => false ),
            array( 'name' => 'Chefchaouen',     'lat' => '35.1688',  'lng' => '-5.2636',  'popular' => true  ),
        ),

        'SD' => array(
            array( 'name' => 'Khartoum',        'lat' => '15.5007',  'lng' => '32.5599',  'popular' => true  ),
            array( 'name' => 'Omdurman',        'lat' => '15.6445',  'lng' => '32.4777',  'popular' => false ),
            array( 'name' => 'Port Sudan',      'lat' => '19.6158',  'lng' => '37.2164',  'popular' => false ),
        ),

        'SO' => array(
            array( 'name' => 'Mogadishu',       'lat' => '2.0469',   'lng' => '45.3182',  'popular' => true  ),
            array( 'name' => 'Hargeisa',        'lat' => '9.5600',   'lng' => '44.0650',  'popular' => false ),
        ),

        'MR' => array(
            array( 'name' => 'Nouakchott',      'lat' => '18.0858',  'lng' => '-15.9785', 'popular' => true  ),
            array( 'name' => 'Nouadhibou',      'lat' => '20.9310',  'lng' => '-17.0347', 'popular' => false ),
        ),

        'PS' => array(
            array( 'name' => 'Gaza',            'lat' => '31.5017',  'lng' => '34.4668',  'popular' => false ),
            array( 'name' => 'Ramallah',        'lat' => '31.9038',  'lng' => '35.2034',  'popular' => false ),
            array( 'name' => 'Bethlehem',       'lat' => '31.7054',  'lng' => '35.2024',  'popular' => true  ),
            array( 'name' => 'Hebron',          'lat' => '31.5326',  'lng' => '35.0998',  'popular' => false ),
        ),

        // ============================================================
        // MIDDLE EAST & NEAR EAST
        // ============================================================

        'TR' => array(
            array( 'name' => 'Istanbul',        'lat' => '41.0082',  'lng' => '28.9784',  'popular' => true  ),
            array( 'name' => 'Ankara',          'lat' => '39.9334',  'lng' => '32.8597',  'popular' => false ),
            array( 'name' => 'Antalya',         'lat' => '36.8969',  'lng' => '30.7133',  'popular' => true  ),
            array( 'name' => 'Cappadocia',      'lat' => '38.6431',  'lng' => '34.8307',  'popular' => true  ),
            array( 'name' => 'Bodrum',          'lat' => '37.0344',  'lng' => '27.4305',  'popular' => true  ),
            array( 'name' => 'Izmir',           'lat' => '38.4192',  'lng' => '27.1287',  'popular' => false ),
            array( 'name' => 'Bursa',           'lat' => '40.1885',  'lng' => '29.0610',  'popular' => false ),
            array( 'name' => 'Trabzon',         'lat' => '41.0015',  'lng' => '39.7178',  'popular' => false ),
            array( 'name' => 'Konya',           'lat' => '37.8746',  'lng' => '32.4932',  'popular' => false ),
            array( 'name' => 'Pamukkale',       'lat' => '37.9213',  'lng' => '29.1189',  'popular' => true  ),
        ),

        'IR' => array(
            array( 'name' => 'Tehran',          'lat' => '35.6892',  'lng' => '51.3890',  'popular' => true  ),
            array( 'name' => 'Isfahan',         'lat' => '32.6546',  'lng' => '51.6680',  'popular' => true  ),
            array( 'name' => 'Shiraz',          'lat' => '29.5918',  'lng' => '52.5837',  'popular' => true  ),
            array( 'name' => 'Mashhad',         'lat' => '36.2605',  'lng' => '59.6168',  'popular' => false ),
        ),

        'IL' => array(
            array( 'name' => 'Tel Aviv',        'lat' => '32.0853',  'lng' => '34.7818',  'popular' => true  ),
            array( 'name' => 'Jerusalem',       'lat' => '31.7683',  'lng' => '35.2137',  'popular' => true  ),
            array( 'name' => 'Haifa',           'lat' => '32.7940',  'lng' => '34.9896',  'popular' => false ),
            array( 'name' => 'Eilat',           'lat' => '29.5577',  'lng' => '34.9519',  'popular' => true  ),
        ),

        'CY' => array(
            array( 'name' => 'Nicosia',         'lat' => '35.1856',  'lng' => '33.3823',  'popular' => true  ),
            array( 'name' => 'Limassol',        'lat' => '34.6786',  'lng' => '33.0413',  'popular' => true  ),
            array( 'name' => 'Paphos',          'lat' => '34.7754',  'lng' => '32.4242',  'popular' => true  ),
        ),

        // ============================================================
        // EUROPE
        // ============================================================

        'GB' => array(
            array( 'name' => 'London',          'lat' => '51.5074',  'lng' => '-0.1278',  'popular' => true  ),
            array( 'name' => 'Manchester',      'lat' => '53.4808',  'lng' => '-2.2426',  'popular' => false ),
            array( 'name' => 'Birmingham',      'lat' => '52.4862',  'lng' => '-1.8904',  'popular' => false ),
            array( 'name' => 'Edinburgh',       'lat' => '55.9533',  'lng' => '-3.1883',  'popular' => true  ),
            array( 'name' => 'Liverpool',       'lat' => '53.4084',  'lng' => '-2.9916',  'popular' => false ),
            array( 'name' => 'Bristol',         'lat' => '51.4545',  'lng' => '-2.5879',  'popular' => false ),
        ),

        'DE' => array(
            array( 'name' => 'Berlin',          'lat' => '52.5200',  'lng' => '13.4050',  'popular' => true  ),
            array( 'name' => 'Munich',          'lat' => '48.1351',  'lng' => '11.5820',  'popular' => true  ),
            array( 'name' => 'Hamburg',         'lat' => '53.5753',  'lng' => '10.0153',  'popular' => false ),
            array( 'name' => 'Frankfurt',       'lat' => '50.1109',  'lng' => '8.6821',   'popular' => false ),
            array( 'name' => 'Cologne',         'lat' => '50.9333',  'lng' => '6.9500',   'popular' => false ),
            array( 'name' => 'Düsseldorf',      'lat' => '51.2217',  'lng' => '6.7762',   'popular' => false ),
        ),

        'FR' => array(
            array( 'name' => 'Paris',           'lat' => '48.8566',  'lng' => '2.3522',   'popular' => true  ),
            array( 'name' => 'Nice',            'lat' => '43.7102',  'lng' => '7.2620',   'popular' => true  ),
            array( 'name' => 'Lyon',            'lat' => '45.7640',  'lng' => '4.8357',   'popular' => false ),
            array( 'name' => 'Marseille',       'lat' => '43.2965',  'lng' => '5.3698',   'popular' => false ),
            array( 'name' => 'Bordeaux',        'lat' => '44.8378',  'lng' => '-0.5792',  'popular' => false ),
            array( 'name' => 'Cannes',          'lat' => '43.5528',  'lng' => '7.0174',   'popular' => true  ),
        ),

        'IT' => array(
            array( 'name' => 'Rome',            'lat' => '41.9028',  'lng' => '12.4964',  'popular' => true  ),
            array( 'name' => 'Milan',           'lat' => '45.4654',  'lng' => '9.1859',   'popular' => true  ),
            array( 'name' => 'Venice',          'lat' => '45.4408',  'lng' => '12.3155',  'popular' => true  ),
            array( 'name' => 'Florence',        'lat' => '43.7696',  'lng' => '11.2558',  'popular' => true  ),
            array( 'name' => 'Naples',          'lat' => '40.8518',  'lng' => '14.2681',  'popular' => false ),
            array( 'name' => 'Amalfi',          'lat' => '40.6340',  'lng' => '14.6027',  'popular' => true  ),
            array( 'name' => 'Sicily',          'lat' => '37.5994',  'lng' => '14.0154',  'popular' => true  ),
        ),

        'ES' => array(
            array( 'name' => 'Madrid',          'lat' => '40.4168',  'lng' => '-3.7038',  'popular' => true  ),
            array( 'name' => 'Barcelona',       'lat' => '41.3851',  'lng' => '2.1734',   'popular' => true  ),
            array( 'name' => 'Seville',         'lat' => '37.3891',  'lng' => '-5.9845',  'popular' => false ),
            array( 'name' => 'Granada',         'lat' => '37.1773',  'lng' => '-3.5986',  'popular' => false ),
            array( 'name' => 'Valencia',        'lat' => '39.4699',  'lng' => '-0.3763',  'popular' => false ),
            array( 'name' => 'Ibiza',           'lat' => '38.9067',  'lng' => '1.4206',   'popular' => true  ),
            array( 'name' => 'Malaga',          'lat' => '36.7213',  'lng' => '-4.4213',  'popular' => true  ),
        ),

        'NL' => array(
            array( 'name' => 'Amsterdam',       'lat' => '52.3676',  'lng' => '4.9041',   'popular' => true  ),
            array( 'name' => 'Rotterdam',       'lat' => '51.9244',  'lng' => '4.4777',   'popular' => false ),
            array( 'name' => 'The Hague',       'lat' => '52.0705',  'lng' => '4.3007',   'popular' => false ),
        ),

        'BE' => array(
            array( 'name' => 'Brussels',        'lat' => '50.8503',  'lng' => '4.3517',   'popular' => true  ),
            array( 'name' => 'Bruges',          'lat' => '51.2093',  'lng' => '3.2247',   'popular' => true  ),
            array( 'name' => 'Ghent',           'lat' => '51.0543',  'lng' => '3.7174',   'popular' => false ),
        ),

        'SE' => array(
            array( 'name' => 'Stockholm',       'lat' => '59.3293',  'lng' => '18.0686',  'popular' => true  ),
            array( 'name' => 'Gothenburg',      'lat' => '57.7089',  'lng' => '11.9746',  'popular' => false ),
            array( 'name' => 'Malmo',           'lat' => '55.6050',  'lng' => '13.0038',  'popular' => false ),
        ),

        'NO' => array(
            array( 'name' => 'Oslo',            'lat' => '59.9139',  'lng' => '10.7522',  'popular' => true  ),
            array( 'name' => 'Bergen',          'lat' => '60.3913',  'lng' => '5.3221',   'popular' => true  ),
            array( 'name' => 'Tromsø',          'lat' => '69.6492',  'lng' => '18.9553',  'popular' => true  ),
        ),

        'DK' => array(
            array( 'name' => 'Copenhagen',      'lat' => '55.6761',  'lng' => '12.5683',  'popular' => true  ),
            array( 'name' => 'Aarhus',          'lat' => '56.1629',  'lng' => '10.2039',  'popular' => false ),
        ),

        'FI' => array(
            array( 'name' => 'Helsinki',        'lat' => '60.1699',  'lng' => '24.9384',  'popular' => true  ),
            array( 'name' => 'Rovaniemi',       'lat' => '66.5039',  'lng' => '25.7294',  'popular' => true  ),
        ),

        'PT' => array(
            array( 'name' => 'Lisbon',          'lat' => '38.7223',  'lng' => '-9.1393',  'popular' => true  ),
            array( 'name' => 'Porto',           'lat' => '41.1579',  'lng' => '-8.6291',  'popular' => true  ),
            array( 'name' => 'Algarve',         'lat' => '37.0179',  'lng' => '-7.9304',  'popular' => true  ),
            array( 'name' => 'Madeira',         'lat' => '32.7607',  'lng' => '-16.9595', 'popular' => true  ),
        ),

        'GR' => array(
            array( 'name' => 'Athens',          'lat' => '37.9838',  'lng' => '23.7275',  'popular' => true  ),
            array( 'name' => 'Santorini',       'lat' => '36.3932',  'lng' => '25.4615',  'popular' => true  ),
            array( 'name' => 'Mykonos',         'lat' => '37.4467',  'lng' => '25.3289',  'popular' => true  ),
            array( 'name' => 'Thessaloniki',    'lat' => '40.6401',  'lng' => '22.9444',  'popular' => false ),
            array( 'name' => 'Crete',           'lat' => '35.2401',  'lng' => '24.8093',  'popular' => true  ),
            array( 'name' => 'Rhodes',          'lat' => '36.4341',  'lng' => '28.2176',  'popular' => true  ),
        ),

        'PL' => array(
            array( 'name' => 'Warsaw',          'lat' => '52.2297',  'lng' => '21.0122',  'popular' => true  ),
            array( 'name' => 'Krakow',          'lat' => '50.0647',  'lng' => '19.9450',  'popular' => true  ),
            array( 'name' => 'Gdansk',          'lat' => '54.3520',  'lng' => '18.6466',  'popular' => false ),
        ),

        'RU' => array(
            array( 'name' => 'Moscow',          'lat' => '55.7558',  'lng' => '37.6173',  'popular' => true  ),
            array( 'name' => 'Saint Petersburg','lat' => '59.9311',  'lng' => '30.3609',  'popular' => true  ),
            array( 'name' => 'Sochi',           'lat' => '43.5855',  'lng' => '39.7231',  'popular' => true  ),
            array( 'name' => 'Kazan',           'lat' => '55.8304',  'lng' => '49.0661',  'popular' => false ),
        ),

        'UA' => array(
            array( 'name' => 'Kyiv',            'lat' => '50.4501',  'lng' => '30.5234',  'popular' => true  ),
            array( 'name' => 'Lviv',            'lat' => '49.8397',  'lng' => '24.0297',  'popular' => true  ),
            array( 'name' => 'Odessa',          'lat' => '46.4825',  'lng' => '30.7233',  'popular' => false ),
        ),

        'CH' => array(
            array( 'name' => 'Zurich',          'lat' => '47.3769',  'lng' => '8.5417',   'popular' => true  ),
            array( 'name' => 'Geneva',          'lat' => '46.2044',  'lng' => '6.1432',   'popular' => true  ),
            array( 'name' => 'Bern',            'lat' => '46.9480',  'lng' => '7.4474',   'popular' => false ),
            array( 'name' => 'Interlaken',      'lat' => '46.6863',  'lng' => '7.8632',   'popular' => true  ),
        ),

        'AT' => array(
            array( 'name' => 'Vienna',          'lat' => '48.2082',  'lng' => '16.3738',  'popular' => true  ),
            array( 'name' => 'Salzburg',        'lat' => '47.8095',  'lng' => '13.0550',  'popular' => true  ),
            array( 'name' => 'Innsbruck',       'lat' => '47.2692',  'lng' => '11.4041',  'popular' => true  ),
        ),

        'CZ' => array(
            array( 'name' => 'Prague',          'lat' => '50.0755',  'lng' => '14.4378',  'popular' => true  ),
            array( 'name' => 'Brno',            'lat' => '49.1951',  'lng' => '16.6068',  'popular' => false ),
        ),

        'HU' => array(
            array( 'name' => 'Budapest',        'lat' => '47.4979',  'lng' => '19.0402',  'popular' => true  ),
            array( 'name' => 'Debrecen',        'lat' => '47.5316',  'lng' => '21.6273',  'popular' => false ),
        ),

        'RO' => array(
            array( 'name' => 'Bucharest',       'lat' => '44.4268',  'lng' => '26.1025',  'popular' => true  ),
            array( 'name' => 'Cluj-Napoca',     'lat' => '46.7712',  'lng' => '23.6236',  'popular' => false ),
            array( 'name' => 'Brasov',          'lat' => '45.6427',  'lng' => '25.5887',  'popular' => false ),
        ),

        'HR' => array(
            array( 'name' => 'Zagreb',          'lat' => '45.8150',  'lng' => '15.9819',  'popular' => true  ),
            array( 'name' => 'Dubrovnik',       'lat' => '42.6507',  'lng' => '18.0944',  'popular' => true  ),
            array( 'name' => 'Split',           'lat' => '43.5081',  'lng' => '16.4402',  'popular' => true  ),
        ),

        'BG' => array(
            array( 'name' => 'Sofia',           'lat' => '42.6977',  'lng' => '23.3219',  'popular' => true  ),
            array( 'name' => 'Plovdiv',         'lat' => '42.1354',  'lng' => '24.7453',  'popular' => false ),
            array( 'name' => 'Varna',           'lat' => '43.2141',  'lng' => '27.9147',  'popular' => true  ),
        ),

        'RS' => array(
            array( 'name' => 'Belgrade',        'lat' => '44.8176',  'lng' => '20.4633',  'popular' => true  ),
            array( 'name' => 'Novi Sad',        'lat' => '45.2671',  'lng' => '19.8335',  'popular' => false ),
        ),

        'SK' => array(
            array( 'name' => 'Bratislava',      'lat' => '48.1486',  'lng' => '17.1077',  'popular' => true  ),
            array( 'name' => 'Kosice',          'lat' => '48.7164',  'lng' => '21.2611',  'popular' => false ),
        ),

        'IE' => array(
            array( 'name' => 'Dublin',          'lat' => '53.3498',  'lng' => '-6.2603',  'popular' => true  ),
            array( 'name' => 'Cork',            'lat' => '51.8985',  'lng' => '-8.4756',  'popular' => false ),
            array( 'name' => 'Galway',          'lat' => '53.2707',  'lng' => '-9.0568',  'popular' => false ),
        ),

        // ============================================================
        // AMERICAS
        // ============================================================

        'US' => array(
            array( 'name' => 'New York',        'lat' => '40.7128',  'lng' => '-74.0060', 'popular' => true  ),
            array( 'name' => 'Los Angeles',     'lat' => '34.0522',  'lng' => '-118.2437','popular' => true  ),
            array( 'name' => 'Miami',           'lat' => '25.7617',  'lng' => '-80.1918', 'popular' => true  ),
            array( 'name' => 'Chicago',         'lat' => '41.8781',  'lng' => '-87.6298', 'popular' => false ),
            array( 'name' => 'Las Vegas',       'lat' => '36.1699',  'lng' => '-115.1398','popular' => true  ),
            array( 'name' => 'Orlando',         'lat' => '28.5383',  'lng' => '-81.3792', 'popular' => true  ),
            array( 'name' => 'San Francisco',   'lat' => '37.7749',  'lng' => '-122.4194','popular' => true  ),
            array( 'name' => 'Washington DC',   'lat' => '38.9072',  'lng' => '-77.0369', 'popular' => false ),
            array( 'name' => 'Boston',          'lat' => '42.3601',  'lng' => '-71.0589', 'popular' => false ),
            array( 'name' => 'Houston',         'lat' => '29.7604',  'lng' => '-95.3698', 'popular' => false ),
        ),

        'CA' => array(
            array( 'name' => 'Toronto',         'lat' => '43.6532',  'lng' => '-79.3832', 'popular' => true  ),
            array( 'name' => 'Vancouver',       'lat' => '49.2827',  'lng' => '-123.1207','popular' => true  ),
            array( 'name' => 'Montreal',        'lat' => '45.5017',  'lng' => '-73.5673', 'popular' => true  ),
            array( 'name' => 'Calgary',         'lat' => '51.0447',  'lng' => '-114.0719','popular' => false ),
            array( 'name' => 'Ottawa',          'lat' => '45.4215',  'lng' => '-75.6972', 'popular' => false ),
        ),

        'MX' => array(
            array( 'name' => 'Mexico City',     'lat' => '19.4326',  'lng' => '-99.1332', 'popular' => true  ),
            array( 'name' => 'Cancun',          'lat' => '21.1619',  'lng' => '-86.8515', 'popular' => true  ),
            array( 'name' => 'Guadalajara',     'lat' => '20.6597',  'lng' => '-103.3496','popular' => false ),
            array( 'name' => 'Playa del Carmen','lat' => '20.6296',  'lng' => '-87.0739', 'popular' => true  ),
        ),

        'BR' => array(
            array( 'name' => 'Rio de Janeiro',  'lat' => '-22.9068', 'lng' => '-43.1729', 'popular' => true  ),
            array( 'name' => 'Sao Paulo',       'lat' => '-23.5505', 'lng' => '-46.6333', 'popular' => true  ),
            array( 'name' => 'Salvador',        'lat' => '-12.9714', 'lng' => '-38.5014', 'popular' => false ),
            array( 'name' => 'Florianopolis',   'lat' => '-27.5954', 'lng' => '-48.5480', 'popular' => false ),
        ),

        'AR' => array(
            array( 'name' => 'Buenos Aires',    'lat' => '-34.6037', 'lng' => '-58.3816', 'popular' => true  ),
            array( 'name' => 'Mendoza',         'lat' => '-32.8895', 'lng' => '-68.8458', 'popular' => false ),
            array( 'name' => 'Bariloche',       'lat' => '-41.1335', 'lng' => '-71.3103', 'popular' => true  ),
        ),

        'CO' => array(
            array( 'name' => 'Bogota',          'lat' => '4.7110',   'lng' => '-74.0721', 'popular' => true  ),
            array( 'name' => 'Medellin',        'lat' => '6.2442',   'lng' => '-75.5812', 'popular' => true  ),
            array( 'name' => 'Cartagena',       'lat' => '10.3932',  'lng' => '-75.4832', 'popular' => true  ),
        ),

        'CL' => array(
            array( 'name' => 'Santiago',        'lat' => '-33.4489', 'lng' => '-70.6693', 'popular' => true  ),
            array( 'name' => 'Valparaiso',      'lat' => '-33.0472', 'lng' => '-71.6127', 'popular' => false ),
        ),

        'PE' => array(
            array( 'name' => 'Lima',            'lat' => '-12.0464', 'lng' => '-77.0428', 'popular' => true  ),
            array( 'name' => 'Cusco',           'lat' => '-13.5320', 'lng' => '-71.9675', 'popular' => true  ),
            array( 'name' => 'Machu Picchu',    'lat' => '-13.1631', 'lng' => '-72.5450', 'popular' => true  ),
        ),

        // ============================================================
        // ASIA
        // ============================================================

        'CN' => array(
            array( 'name' => 'Beijing',         'lat' => '39.9042',  'lng' => '116.4074', 'popular' => true  ),
            array( 'name' => 'Shanghai',        'lat' => '31.2304',  'lng' => '121.4737', 'popular' => true  ),
            array( 'name' => 'Guangzhou',       'lat' => '23.1291',  'lng' => '113.2644', 'popular' => false ),
            array( 'name' => 'Shenzhen',        'lat' => '22.5431',  'lng' => '114.0579', 'popular' => false ),
            array( 'name' => 'Chengdu',         'lat' => '30.5728',  'lng' => '104.0668', 'popular' => false ),
            array( 'name' => "Xi'an",           'lat' => '34.3416',  'lng' => '108.9398', 'popular' => true  ),
        ),

        'JP' => array(
            array( 'name' => 'Tokyo',           'lat' => '35.6762',  'lng' => '139.6503', 'popular' => true  ),
            array( 'name' => 'Osaka',           'lat' => '34.6937',  'lng' => '135.5023', 'popular' => true  ),
            array( 'name' => 'Kyoto',           'lat' => '35.0116',  'lng' => '135.7681', 'popular' => true  ),
            array( 'name' => 'Hiroshima',       'lat' => '34.3853',  'lng' => '132.4553', 'popular' => false ),
            array( 'name' => 'Hokkaido',        'lat' => '43.2203',  'lng' => '142.8635', 'popular' => true  ),
        ),

        'IN' => array(
            array( 'name' => 'Mumbai',          'lat' => '19.0760',  'lng' => '72.8777',  'popular' => true  ),
            array( 'name' => 'Delhi',           'lat' => '28.6139',  'lng' => '77.2090',  'popular' => true  ),
            array( 'name' => 'Goa',             'lat' => '15.2993',  'lng' => '74.1240',  'popular' => true  ),
            array( 'name' => 'Agra',            'lat' => '27.1767',  'lng' => '78.0081',  'popular' => true  ),
            array( 'name' => 'Jaipur',          'lat' => '26.9124',  'lng' => '75.7873',  'popular' => false ),
            array( 'name' => 'Bangalore',       'lat' => '12.9716',  'lng' => '77.5946',  'popular' => false ),
            array( 'name' => 'Chennai',         'lat' => '13.0827',  'lng' => '80.2707',  'popular' => false ),
            array( 'name' => 'Hyderabad',       'lat' => '17.3850',  'lng' => '78.4867',  'popular' => false ),
            array( 'name' => 'Kerala',          'lat' => '10.8505',  'lng' => '76.2711',  'popular' => true  ),
        ),

        'KR' => array(
            array( 'name' => 'Seoul',           'lat' => '37.5665',  'lng' => '126.9780', 'popular' => true  ),
            array( 'name' => 'Busan',           'lat' => '35.1796',  'lng' => '129.0756', 'popular' => true  ),
            array( 'name' => 'Jeju',            'lat' => '33.4996',  'lng' => '126.5312', 'popular' => true  ),
        ),

        'PK' => array(
            array( 'name' => 'Karachi',         'lat' => '24.8607',  'lng' => '67.0011',  'popular' => true  ),
            array( 'name' => 'Lahore',          'lat' => '31.5204',  'lng' => '74.3587',  'popular' => true  ),
            array( 'name' => 'Islamabad',       'lat' => '33.6844',  'lng' => '73.0479',  'popular' => false ),
            array( 'name' => 'Peshawar',        'lat' => '34.0151',  'lng' => '71.5249',  'popular' => false ),
        ),

        'BD' => array(
            array( 'name' => 'Dhaka',           'lat' => '23.8103',  'lng' => '90.4125',  'popular' => true  ),
            array( 'name' => 'Chittagong',      'lat' => '22.3569',  'lng' => '91.7832',  'popular' => false ),
            array( 'name' => "Cox's Bazar",     'lat' => '21.4272',  'lng' => '91.9918',  'popular' => true  ),
        ),

        'ID' => array(
            array( 'name' => 'Bali',            'lat' => '-8.3405',  'lng' => '115.0920', 'popular' => true  ),
            array( 'name' => 'Jakarta',         'lat' => '-6.2088',  'lng' => '106.8456', 'popular' => false ),
            array( 'name' => 'Lombok',          'lat' => '-8.6500',  'lng' => '116.3249', 'popular' => true  ),
            array( 'name' => 'Yogyakarta',      'lat' => '-7.7956',  'lng' => '110.3695', 'popular' => true  ),
            array( 'name' => 'Surabaya',        'lat' => '-7.2575',  'lng' => '112.7521', 'popular' => false ),
        ),

        'TH' => array(
            array( 'name' => 'Bangkok',         'lat' => '13.7563',  'lng' => '100.5018', 'popular' => true  ),
            array( 'name' => 'Phuket',          'lat' => '7.8804',   'lng' => '98.3923',  'popular' => true  ),
            array( 'name' => 'Chiang Mai',      'lat' => '18.7883',  'lng' => '98.9853',  'popular' => true  ),
            array( 'name' => 'Pattaya',         'lat' => '12.9236',  'lng' => '100.8825', 'popular' => false ),
            array( 'name' => 'Krabi',           'lat' => '8.0863',   'lng' => '98.9063',  'popular' => true  ),
            array( 'name' => 'Koh Samui',       'lat' => '9.5120',   'lng' => '100.0136', 'popular' => true  ),
        ),

        'VN' => array(
            array( 'name' => 'Ho Chi Minh City','lat' => '10.8231',  'lng' => '106.6297', 'popular' => true  ),
            array( 'name' => 'Hanoi',           'lat' => '21.0285',  'lng' => '105.8542', 'popular' => true  ),
            array( 'name' => 'Da Nang',         'lat' => '16.0544',  'lng' => '108.2022', 'popular' => true  ),
            array( 'name' => 'Hoi An',          'lat' => '15.8800',  'lng' => '108.3380', 'popular' => true  ),
            array( 'name' => 'Ha Long Bay',     'lat' => '20.9101',  'lng' => '107.1839', 'popular' => true  ),
        ),

        'PH' => array(
            array( 'name' => 'Manila',          'lat' => '14.5995',  'lng' => '120.9842', 'popular' => true  ),
            array( 'name' => 'Cebu',            'lat' => '10.3157',  'lng' => '123.8854', 'popular' => true  ),
            array( 'name' => 'Palawan',         'lat' => '9.8349',   'lng' => '118.7384', 'popular' => true  ),
            array( 'name' => 'Boracay',         'lat' => '11.9674',  'lng' => '121.9248', 'popular' => true  ),
        ),

        'MY' => array(
            array( 'name' => 'Kuala Lumpur',    'lat' => '3.1390',   'lng' => '101.6869', 'popular' => true  ),
            array( 'name' => 'Penang',          'lat' => '5.4141',   'lng' => '100.3288', 'popular' => true  ),
            array( 'name' => 'Langkawi',        'lat' => '6.3500',   'lng' => '99.8000',  'popular' => true  ),
            array( 'name' => 'Kota Kinabalu',   'lat' => '5.9804',   'lng' => '116.0735', 'popular' => false ),
        ),

        'SG' => array(
            array( 'name' => 'Singapore',       'lat' => '1.3521',   'lng' => '103.8198', 'popular' => true  ),
            array( 'name' => 'Sentosa',         'lat' => '1.2494',   'lng' => '103.8303', 'popular' => true  ),
        ),

        'MM' => array(
            array( 'name' => 'Yangon',          'lat' => '16.8661',  'lng' => '96.1951',  'popular' => true  ),
            array( 'name' => 'Mandalay',        'lat' => '21.9588',  'lng' => '96.0891',  'popular' => false ),
            array( 'name' => 'Bagan',           'lat' => '21.1717',  'lng' => '94.8585',  'popular' => true  ),
        ),

        'NP' => array(
            array( 'name' => 'Kathmandu',       'lat' => '27.7172',  'lng' => '85.3240',  'popular' => true  ),
            array( 'name' => 'Pokhara',         'lat' => '28.2096',  'lng' => '83.9856',  'popular' => true  ),
        ),

        'LK' => array(
            array( 'name' => 'Colombo',         'lat' => '6.9271',   'lng' => '79.8612',  'popular' => true  ),
            array( 'name' => 'Kandy',           'lat' => '7.2906',   'lng' => '80.6337',  'popular' => true  ),
            array( 'name' => 'Galle',           'lat' => '6.0535',   'lng' => '80.2210',  'popular' => true  ),
        ),

        'KZ' => array(
            array( 'name' => 'Almaty',          'lat' => '43.2220',  'lng' => '76.8512',  'popular' => true  ),
            array( 'name' => 'Astana',          'lat' => '51.1801',  'lng' => '71.4460',  'popular' => true  ),
        ),

        'UZ' => array(
            array( 'name' => 'Tashkent',        'lat' => '41.2995',  'lng' => '69.2401',  'popular' => true  ),
            array( 'name' => 'Samarkand',       'lat' => '39.6542',  'lng' => '66.9597',  'popular' => true  ),
            array( 'name' => 'Bukhara',         'lat' => '39.7747',  'lng' => '64.4286',  'popular' => true  ),
        ),

        'AZ' => array(
            array( 'name' => 'Baku',            'lat' => '40.4093',  'lng' => '49.8671',  'popular' => true  ),
            array( 'name' => 'Ganja',           'lat' => '40.6828',  'lng' => '46.3606',  'popular' => false ),
        ),

        'AM' => array(
            array( 'name' => 'Yerevan',         'lat' => '40.1872',  'lng' => '44.5152',  'popular' => true  ),
            array( 'name' => 'Gyumri',          'lat' => '40.7942',  'lng' => '43.8453',  'popular' => false ),
        ),

        'GE' => array(
            array( 'name' => 'Tbilisi',         'lat' => '41.6938',  'lng' => '44.8015',  'popular' => true  ),
            array( 'name' => 'Batumi',          'lat' => '41.6168',  'lng' => '41.6367',  'popular' => true  ),
        ),

        // ============================================================
        // AFRICA
        // ============================================================

        'NG' => array(
            array( 'name' => 'Lagos',           'lat' => '6.5244',   'lng' => '3.3792',   'popular' => true  ),
            array( 'name' => 'Abuja',           'lat' => '9.0765',   'lng' => '7.3986',   'popular' => false ),
            array( 'name' => 'Kano',            'lat' => '12.0022',  'lng' => '8.5920',   'popular' => false ),
        ),

        'ZA' => array(
            array( 'name' => 'Cape Town',       'lat' => '-33.9249', 'lng' => '18.4241',  'popular' => true  ),
            array( 'name' => 'Johannesburg',    'lat' => '-26.2041', 'lng' => '28.0473',  'popular' => true  ),
            array( 'name' => 'Durban',          'lat' => '-29.8587', 'lng' => '31.0218',  'popular' => false ),
            array( 'name' => 'Pretoria',        'lat' => '-25.7461', 'lng' => '28.1881',  'popular' => false ),
        ),

        'KE' => array(
            array( 'name' => 'Nairobi',         'lat' => '-1.2921',  'lng' => '36.8219',  'popular' => true  ),
            array( 'name' => 'Mombasa',         'lat' => '-4.0435',  'lng' => '39.6682',  'popular' => true  ),
            array( 'name' => 'Masai Mara',      'lat' => '-1.5021',  'lng' => '35.1448',  'popular' => true  ),
        ),

        'ET' => array(
            array( 'name' => 'Addis Ababa',     'lat' => '9.0320',   'lng' => '38.7469',  'popular' => true  ),
            array( 'name' => 'Lalibela',        'lat' => '12.0317',  'lng' => '39.0447',  'popular' => true  ),
        ),

        'GH' => array(
            array( 'name' => 'Accra',           'lat' => '5.6037',   'lng' => '-0.1870',  'popular' => true  ),
            array( 'name' => 'Kumasi',          'lat' => '6.6885',   'lng' => '-1.6244',  'popular' => false ),
        ),

        'TZ' => array(
            array( 'name' => 'Dar es Salaam',   'lat' => '-6.7924',  'lng' => '39.2083',  'popular' => true  ),
            array( 'name' => 'Zanzibar',        'lat' => '-6.1659',  'lng' => '39.1999',  'popular' => true  ),
            array( 'name' => 'Arusha',          'lat' => '-3.3869',  'lng' => '36.6830',  'popular' => true  ),
        ),

        // ============================================================
        // OCEANIA
        // ============================================================

        'AU' => array(
            array( 'name' => 'Sydney',          'lat' => '-33.8688', 'lng' => '151.2093', 'popular' => true  ),
            array( 'name' => 'Melbourne',       'lat' => '-37.8136', 'lng' => '144.9631', 'popular' => true  ),
            array( 'name' => 'Brisbane',        'lat' => '-27.4698', 'lng' => '153.0251', 'popular' => false ),
            array( 'name' => 'Gold Coast',      'lat' => '-28.0167', 'lng' => '153.4000', 'popular' => true  ),
            array( 'name' => 'Perth',           'lat' => '-31.9505', 'lng' => '115.8605', 'popular' => false ),
            array( 'name' => 'Cairns',          'lat' => '-16.9186', 'lng' => '145.7781', 'popular' => true  ),
        ),

        'NZ' => array(
            array( 'name' => 'Auckland',        'lat' => '-36.8509', 'lng' => '174.7645', 'popular' => true  ),
            array( 'name' => 'Wellington',      'lat' => '-41.2865', 'lng' => '174.7762', 'popular' => false ),
            array( 'name' => 'Queenstown',      'lat' => '-45.0312', 'lng' => '168.6626', 'popular' => true  ),
            array( 'name' => 'Christchurch',    'lat' => '-43.5320', 'lng' => '172.6306', 'popular' => false ),
        ),
    );
}


/**
 * Get cities for a specific country by ISO code.
 *
 * @since  1.0.0
 * @param  string $country_code ISO country code (e.g. 'EG').
 * @return array                Array of city data arrays.
 */
function moga_get_cities_by_country( $country_code ) {
    $all_cities   = moga_get_all_cities();
    $country_code = strtoupper( $country_code );
    return isset( $all_cities[ $country_code ] ) ? $all_cities[ $country_code ] : array();
}


/**
 * Get cities formatted for a select dropdown.
 *
 * @since  1.0.0
 * @param  string $country_code ISO country code.
 * @return array                name => name pairs for dropdown.
 */
function moga_get_cities_dropdown( $country_code ) {
    $cities = moga_get_cities_by_country( $country_code );
    $result = array( '' => __( '— Select City —', 'moga-travel-core' ) );
    foreach ( $cities as $city ) {
        $result[ $city['name'] ] = $city['name'];
    }
    return $result;
}


/**
 * Get popular cities for a country.
 *
 * @since  1.0.0
 * @param  string $country_code ISO country code.
 * @return array
 */
function moga_get_popular_cities_by_country( $country_code ) {
    $cities = moga_get_cities_by_country( $country_code );
    return array_filter( $cities, function( $city ) {
        return ! empty( $city['popular'] );
    } );
}


/**
 * Get all popular cities across all countries.
 *
 * @since  1.0.0
 * @return array
 */
function moga_get_all_popular_cities() {
    $all_cities = moga_get_all_cities();
    $popular    = array();
    foreach ( $all_cities as $country_code => $cities ) {
        foreach ( $cities as $city ) {
            if ( ! empty( $city['popular'] ) ) {
                $city['country_code'] = $country_code;
                $popular[]            = $city;
            }
        }
    }
    return $popular;
}


/**
 * Search cities by name across all countries.
 *
 * @since  1.0.0
 * @param  string $query  Search query.
 * @param  int    $limit  Max results.
 * @return array
 */
function moga_search_cities( $query, $limit = 10 ) {
    $all_cities = moga_get_all_cities();
    $results    = array();
    $query      = strtolower( trim( $query ) );

    if ( empty( $query ) ) {
        return array();
    }

    foreach ( $all_cities as $country_code => $cities ) {
        foreach ( $cities as $city ) {
            if ( strpos( strtolower( $city['name'] ), $query ) !== false ) {
                $city['country_code'] = $country_code;
                $results[]            = $city;
                if ( count( $results ) >= $limit ) {
                    return $results;
                }
            }
        }
    }

    return $results;
}


/**
 * Auto-sync a city to the moga_location taxonomy.
 *
 * Called automatically when a property or tour is saved.
 * Creates country and city taxonomy terms in the background
 * if they don't already exist.
 * Admin never needs to manually manage the Locations taxonomy.
 *
 * @since  1.0.0
 * @param  string $country_code ISO country code (e.g. 'EG').
 * @param  string $city_name    City name (e.g. 'Cairo').
 * @return int|null             City term ID or null on failure.
 */
function moga_sync_city_to_taxonomy( $country_code, $city_name ) {

    if ( empty( $country_code ) || empty( $city_name ) ) {
        return null;
    }

    $country_code = strtoupper( $country_code );
    $taxonomy     = 'moga_location';

    // ---- Step 1: Get or create the country term ----
    $country_data = moga_get_country( $country_code );
    $country_name = $country_data ? $country_data['name'] : $country_code;
    $country_slug = sanitize_title( $country_name );

    $country_term = get_term_by( 'slug', $country_slug, $taxonomy );

    if ( ! $country_term ) {
        $result = wp_insert_term( $country_name, $taxonomy, array(
            'slug' => $country_slug,
        ) );

        if ( is_wp_error( $result ) ) {
            return null;
        }

        $country_term_id = $result['term_id'];

        // Set country meta.
        update_term_meta( $country_term_id, 'moga_level',        'country' );
        update_term_meta( $country_term_id, 'moga_country_code', $country_code );
        update_term_meta( $country_term_id, 'moga_flag',
            $country_data ? $country_data['flag'] : '' );
        update_term_meta( $country_term_id, 'moga_order',        99 );

    } else {
        $country_term_id = $country_term->term_id;
    }

    // ---- Step 2: Get or create the city term ----
    $city_slug = sanitize_title( $country_code . '-' . $city_name );
    $city_term = get_term_by( 'slug', $city_slug, $taxonomy );

    if ( ! $city_term ) {

        $result = wp_insert_term( $city_name, $taxonomy, array(
            'slug'   => $city_slug,
            'parent' => $country_term_id,
        ) );

        if ( is_wp_error( $result ) ) {
            return null;
        }

        $city_term_id = $result['term_id'];

        // Set city meta — try to get GPS from cities.php.
        $cities    = moga_get_cities_by_country( $country_code );
        $city_data = null;

        foreach ( $cities as $city ) {
            if ( strtolower( $city['name'] ) === strtolower( $city_name ) ) {
                $city_data = $city;
                break;
            }
        }

        update_term_meta( $city_term_id, 'moga_level',        'city' );
        update_term_meta( $city_term_id, 'moga_country_code', $country_code );
        update_term_meta( $city_term_id, 'moga_flag',
            $country_data ? $country_data['flag'] : '' );
        update_term_meta( $city_term_id, 'moga_latitude',
            $city_data ? $city_data['lat'] : '' );
        update_term_meta( $city_term_id, 'moga_longitude',
            $city_data ? $city_data['lng'] : '' );
        update_term_meta( $city_term_id, 'moga_popular',
            $city_data ? (int) $city_data['popular'] : 0 );
        update_term_meta( $city_term_id, 'moga_order', 99 );

    } else {
        $city_term_id = $city_term->term_id;
    }

    return $city_term_id;
}