<?php
/**
 * Default site configuration and content.
 *
 * storage/site.json overrides anything here (deep merge), so the admin panel
 * never edits this file — it only writes the JSON overlay.
 */

return [

    /* ------------------------------------------------------------------
     * Languages. The default one has no URL prefix (carclub.az/),
     * the others live under /az/, /ru/, /ar/ — same scheme WordPress used.
     * ------------------------------------------------------------------ */
    'default_lang' => 'en',
    'languages' => [
        'en' => ['name' => 'English',    'short' => 'EN', 'locale' => 'en_US', 'dir' => 'ltr', 'prefix' => ''],
        'az' => ['name' => 'Azərbaycan', 'short' => 'AZ', 'locale' => 'az_AZ', 'dir' => 'ltr', 'prefix' => 'az'],
        'ru' => ['name' => 'Русский',    'short' => 'RU', 'locale' => 'ru_RU', 'dir' => 'ltr', 'prefix' => 'ru'],
        'ar' => ['name' => 'العربية',     'short' => 'AR', 'locale' => 'ar_AE', 'dir' => 'rtl', 'prefix' => 'ar'],
    ],

    /* Page slugs — kept identical to the old WordPress URLs so nothing 404s. */
    'slugs' => [
        'cars'    => 'masinlar',
        'about'   => 'haqqimizda',
        'contact' => 'elaqe',
    ],

    /* ------------------------------------------------------------------
     * Contacts and social links (editable in the admin panel)
     * ------------------------------------------------------------------ */
    'contacts' => [
        'phone1'     => '+994 55 608 08 06',
        'phone2'     => '+994 77 380 95 85',
        'whatsapp'   => '994556080806',
        'whatsapp2'  => '994773809585',
        'email'      => 'info@carclub.az',
        'tiktok'     => 'https://vt.tiktok.com/ZSLGJaBq5/',
        'instagram'  => '',
        'facebook'   => '',
        'map_query'  => 'baku',
    ],

    'settings' => [
        'brand'          => 'Carclub',
        'notify_email'   => 'info@carclub.az',
        'og_image'       => 'img/site/rentacar.jpg',
        'hero_image'     => 'img/site/rent-a-car-for-someone-else.rendition.medium.jpg',
        'about_image'    => 'img/site/rentacar.jpg',
        'about_bg'       => 'img/site/rentcar.jpg',
        'home_cars'      => 6,
        'search_visible' => true,
        'ga_id'          => '',
    ],

    /* Brand logos on the home page strip */
    'brands' => [
        ['file' => 'img/brands/hyundai.webp',     'name' => 'Hyundai'],
        ['file' => 'img/brands/range-rover.webp', 'name' => 'Land Rover'],
        ['file' => 'img/brands/ford.webp',        'name' => 'Ford'],
        ['file' => 'img/brands/lexus.webp',       'name' => 'Lexus'],
        ['file' => 'img/brands/kia.webp',         'name' => 'Kia'],
        ['file' => 'img/brands/toyota.webp',      'name' => 'Toyota'],
    ],

    /* ------------------------------------------------------------------
     * Editable content, per language
     * ------------------------------------------------------------------ */
    'content' => [

        /* ============================= EN ============================= */
        'en' => [
            'home' => [
                'seo_title' => 'Car Rental in Baku — Affordable and Convenient | Carclub.az',
                'seo_desc'  => 'Car rental in Baku at the best prices. A wide choice of new and comfortable cars, fast delivery and round-the-clock support.',
                'eyebrow'   => 'Carclub · Rent a car',
                'title'     => 'Rent a Car in',
                'title_accent' => 'Baku',
                'text'      => 'New, fully insured cars with free delivery to the airport or to your address. Transparent terms, no hidden fees — pick a car and we will bring it to you.',
                'stat1_v' => '24/7',  'stat1_l' => 'Support',
                'stat4_v' => '100%',  'stat4_l' => 'Insured',
                'f1_title' => 'Free delivery', 'f1_text' => 'We bring the car to the airport or to any address in Baku at no extra cost.',
                'f2_title' => 'Full insurance', 'f2_text' => 'Every car in the fleet is fully insured, so you drive without worrying about the paperwork.',
                'f3_title' => 'Support 24/7',  'f3_text' => 'Call or write on WhatsApp at any hour — we answer and solve the question quickly.',
                'f4_title' => 'New cars',      'f4_text' => 'Kia, Toyota and BMW from 2019 to 2022 — serviced, clean and ready to go.',
                'about_title' => 'Reliable transport on the best terms',
                'cta_title' => 'Free delivery to the airport or your address',
                'cta_text'  => 'Tell us the car, the dates and the place — we will bring it and hand over the keys. Call or write on WhatsApp.',
            ],
            'about' => [
                'seo_title' => 'About Us — Trusted Car Rental Company in Baku | Carclub.az',
                'seo_desc'  => 'Learn more about our reliable and experienced car rental company in Baku. We value customer satisfaction and offer flexible rental terms.',
                'title'     => 'About us',
                'lead'      => 'Reliable transport on the best terms',
                'text'      => "<p>Nowadays you cannot imagine your life without transport. And if the conversation is about a trip abroad, about visiting sights, about an autonomous trip — the first thing you think about is transport. And here we are, Carclub! We will help you solve this issue more profitably, better and more reliably than other car rental companies.</p>\n<p>We try to provide a comfortable trip without unnecessary questions and commitments. Want to rent a new, reliable, fully insured car on the best conditions? Carclub is waiting for you. Dozens of reviews from our satisfied customers all over the world speak for us.</p>",
            ],
            'cars' => [
                'seo_title' => 'Cars — Wide Range of Cars for Rent in Baku | Carclub.az',
                'seo_desc'  => 'Discover our fleet of cars available for rent in Baku. Modern and comfortable vehicles for every need and budget.',
                'title'     => 'Cars',
                'lead'      => 'Pick a car from the fleet — every one of them is new, serviced and fully insured. Delivery in Baku is free.',
            ],
            'contact' => [
                'seo_title' => 'Contact Us — Fast and Easy Car Rental Support in Baku | Carclub.az',
                'seo_desc'  => 'Get in touch with us for all your car rental questions in Baku. Phone, email and online contact form available for your convenience.',
                'title'     => 'Contact',
                'lead'      => 'Call, write on WhatsApp or leave a request — we answer quickly and around the clock.',
            ],
            'footer_text' => 'Car rental in Baku: new and fully insured cars, free delivery to the airport or to your address, support around the clock.',
        ],

        /* ============================= AZ ============================= */
        'az' => [
            'home' => [
                'seo_title' => 'Bakı Avtomobil İcarəsi — Sərfəli və Rahat Maşın Kirayəsi | Carclub.az',
                'seo_desc'  => 'Bakıda sərfəli qiymətlərlə avtomobil icarəsi. Yeni və komfortlu maşınlar, sürətli xidmət və 24/7 dəstək. Arenda üçün ən yaxşı seçim!',
                'eyebrow'   => 'Carclub · Avtomobil icarəsi',
                'title'     => 'Avtomobil icarəsi',
                'title_accent' => 'Bakıda',
                'text'      => 'Yeni, tam sığortalı avtomobillər — hava limanına və ya ünvanınıza pulsuz çatdırılma. Şəffaf şərtlər, gizli ödəniş yoxdur: avtomobili seçin, qalanını biz edək.',
                'stat1_v' => '24/7',    'stat1_l' => 'Dəstək',
                'stat4_v' => '100%',    'stat4_l' => 'Sığortalı',
                'f1_title' => 'Pulsuz çatdırılma', 'f1_text' => 'Avtomobili hava limanına və ya Bakıda istənilən ünvana əlavə ödənişsiz çatdırırıq.',
                'f2_title' => 'Tam sığorta',       'f2_text' => 'Parkdakı bütün avtomobillər tam sığortalanıb — sənədlərlə bağlı narahatlıq yoxdur.',
                'f3_title' => '24/7 dəstək',       'f3_text' => 'İstənilən vaxt zəng edin və ya WhatsApp-a yazın — tez cavab verib məsələni həll edirik.',
                'f4_title' => 'Yeni avtomobillər', 'f4_text' => '2019–2022 Kia, Toyota və BMW — texniki baxımdan hazır, təmiz və yola hazır.',
                'about_title' => 'Ən sərfəli şərtlərlə etibarlı nəqliyyat',
                'cta_title' => 'Hava limanına və ya ünvana pulsuz çatdırılma',
                'cta_text'  => 'Avtomobili, tarixləri və yeri deyin — gətirib açarları təhvil verək. Zəng edin və ya WhatsApp-a yazın.',
            ],
            'about' => [
                'seo_title' => 'Etibarlı Avtomobil İcarəsi Şirkəti Bakı | Carclub.az',
                'seo_desc'  => 'Bakı şəhərində etibarlı və təcrübəli avtomobil icarəsi şirkəti. Müştəri məmnuniyyəti və sərfəli şərtlər bizim üçün önəmlidir.',
                'title'     => 'Haqqımızda',
                'lead'      => 'Ən sərfəli şərtlərlə etibarlı nəqliyyat',
                'text'      => "<p>Bugünkü gündə siz öz həyatınızı nəqliyyat vasitəsiz təsəvvür edə bilmirsiniz. Və əgər söhbət xaricə səfərdən, baxımlı yerləri və təbiəti görməkdən gedirsə — birinci fikirləşdiyimiz nəqliyyat vasitəsi olur. Və bu zaman biz — Carclub şirkəti sizin köməyinizə gəlirik. Bu məsələni sizə ən əlverişli şərtlərlə, digər şirkətlərdən fərqli üslubda həll etməyə kömək edəcəyik.</p>\n<p>Biz sizi rahat, lazımsız sual və öhdəliksiz bir səyahətlə təmin edəcəyik. Yeni, rahat və tam sığortalı avtomobil icarə etmək istəyirsiniz? Carclub sizi gözləyir. Müştərilərimizdən gələn onlarla xoş rəy bizim işimiz haqqında sizə əlavə məlumat verməkdə kömək olacaq.</p>",
            ],
            'cars' => [
                'seo_title' => 'Bakıda Kirayə Verilən Avtomobillər | Carclub.az',
                'seo_desc'  => 'Bakıda icarəyə verilən ən yeni və rahat avtomobillər. Seçim üçün geniş avtomobil parkı və uyğun qiymətlər.',
                'title'     => 'Maşınlar',
                'lead'      => 'Parkdan avtomobil seçin — hamısı yeni, texniki baxımdan hazır və tam sığortalıdır. Bakı daxilində çatdırılma pulsuzdur.',
            ],
            'contact' => [
                'seo_title' => 'Əlaqə — Bakıda Sürətli Avtomobil İcarəsi Dəstəyi | Carclub.az',
                'seo_desc'  => 'Avtomobil icarəsi ilə bağlı bütün suallar üçün bizimlə əlaqə saxlayın. Telefon, e-poçt və onlayn müraciət forması.',
                'title'     => 'Əlaqə',
                'lead'      => 'Zəng edin, WhatsApp-a yazın və ya sorğu göndərin — gecə-gündüz tez cavab veririk.',
            ],
            'footer_text' => 'Bakıda avtomobil icarəsi: yeni və tam sığortalı avtomobillər, hava limanına və ya ünvana pulsuz çatdırılma, gecə-gündüz dəstək.',
        ],

        /* ============================= RU ============================= */
        'ru' => [
            'home' => [
                'seo_title' => 'Аренда автомобилей в Баку — выгодно и удобно | Carclub.az',
                'seo_desc'  => 'Аренда автомобилей в Баку по лучшим ценам. Большой выбор новых и комфортных машин, быстрая подача и круглосуточная поддержка.',
                'eyebrow'   => 'Carclub · Аренда авто',
                'title'     => 'Аренда авто',
                'title_accent' => 'в Баку',
                'text'      => 'Новые, полностью застрахованные автомобили с бесплатной доставкой в аэропорт или по адресу. Прозрачные условия без скрытых платежей — выберите машину, остальное сделаем мы.',
                'stat1_v' => '24/7',        'stat1_l' => 'Поддержка',
                'stat4_v' => '100%',        'stat4_l' => 'Застрахованы',
                'f1_title' => 'Бесплатная доставка', 'f1_text' => 'Привезём машину в аэропорт или по любому адресу в Баку без доплат.',
                'f2_title' => 'Полная страховка',    'f2_text' => 'Все автомобили парка полностью застрахованы — можно ехать и не думать о бумагах.',
                'f3_title' => 'Поддержка 24/7',      'f3_text' => 'Звоните или пишите в WhatsApp в любое время — ответим и решим вопрос быстро.',
                'f4_title' => 'Новые автомобили',    'f4_text' => 'Kia, Toyota и BMW 2019–2022 годов — обслужены, чистые и готовы к поездке.',
                'about_title' => 'Надёжный транспорт на лучших условиях',
                'cta_title' => 'Бесплатная доставка в аэропорт или по адресу',
                'cta_text'  => 'Скажите, какая машина, на какие даты и куда — привезём и передадим ключи. Звоните или пишите в WhatsApp.',
            ],
            'about' => [
                'seo_title' => 'О нас — надёжная компания по аренде авто в Баку | Carclub.az',
                'seo_desc'  => 'Узнайте больше о нашей надёжной и опытной компании по аренде автомобилей в Баку. Гибкие условия аренды и забота о клиентах — наш приоритет.',
                'title'     => 'О нас',
                'lead'      => 'Надёжный транспорт на лучших условиях',
                'text'      => "<p>В настоящее время мы не можем представить себя без транспорта, а если разговор идёт о поездке за границу, о знакомстве с достопримечательностями страны или о трансферах по городу и регионам, то автомобиль превращается в незаменимую опцию. Позвольте представить вам компанию Carclub, основная задача которой — обеспечивать клиентов надёжным транспортом на самых выгодных условиях.</p>\n<p>Мы обеспечиваем вам комфортное путешествие без лишних вопросов и обязательств. Хотите арендовать новый, надёжный, полностью застрахованный автомобиль на лучших условиях? Carclub к вашим услугам. Сотни положительных отзывов от наших уважаемых клиентов со всего мира свидетельствуют о высоком уровне сервиса — попробуйте и вы!</p>",
            ],
            'cars' => [
                'seo_title' => 'Автомобили — большой выбор машин напрокат в Баку | Carclub.az',
                'seo_desc'  => 'Ознакомьтесь с нашим автопарком для аренды в Баку. Современные и комфортные автомобили для любого запроса и бюджета.',
                'title'     => 'Автомобили',
                'lead'      => 'Выберите машину из автопарка — все они новые, обслужены и полностью застрахованы. Доставка по Баку бесплатная.',
            ],
            'contact' => [
                'seo_title' => 'Контакты — быстрая поддержка по аренде авто в Баку | Carclub.az',
                'seo_desc'  => 'Свяжитесь с нами по любым вопросам аренды автомобилей в Баку. Телефон, электронная почта и онлайн-форма для вашего удобства.',
                'title'     => 'Контакты',
                'lead'      => 'Звоните, пишите в WhatsApp или оставьте заявку — отвечаем быстро и круглосуточно.',
            ],
            'footer_text' => 'Аренда автомобилей в Баку: новые и полностью застрахованные машины, бесплатная доставка в аэропорт или по адресу, круглосуточная поддержка.',
        ],

        /* ============================= AR ============================= */
        'ar' => [
            'home' => [
                'seo_title' => 'تأجير السيارات في باكو — سيارات مريحة وبأسعار مناسبة | Carclub.az',
                'seo_desc'  => 'استأجر سيارة في باكو بأفضل الأسعار! أسطول واسع من السيارات الحديثة والمريحة، خدمة سريعة ودعم على مدار الساعة.',
                'eyebrow'   => 'Carclub · تأجير السيارات',
                'title'     => 'تأجير سيارة في',
                'title_accent' => 'باكو',
                'text'      => 'سيارات جديدة ومؤمّنة بالكامل مع توصيل مجاني إلى المطار أو إلى عنوانك. شروط واضحة بلا رسوم خفية — اختر السيارة ونحن نوصلها إليك.',
                'stat1_v' => '24/7',   'stat1_l' => 'الدعم',
                'stat4_v' => '100%',   'stat4_l' => 'مؤمّنة',
                'f1_title' => 'توصيل مجاني', 'f1_text' => 'نوصل السيارة إلى المطار أو إلى أي عنوان في باكو دون رسوم إضافية.',
                'f2_title' => 'تأمين شامل',  'f2_text' => 'جميع سيارات الأسطول مؤمّنة بالكامل، فتقود دون القلق بشأن الأوراق.',
                'f3_title' => 'دعم 24/7',    'f3_text' => 'اتصل أو راسلنا عبر واتساب في أي وقت — نرد ونحل الأمر بسرعة.',
                'f4_title' => 'سيارات حديثة', 'f4_text' => 'كيا وتويوتا وبي إم دبليو من 2019 إلى 2022 — مُصانة ونظيفة وجاهزة للانطلاق.',
                'about_title' => 'نقل موثوق بأفضل الشروط',
                'cta_title' => 'التوصيل مجاني إلى المطار أو العنوان',
                'cta_text'  => 'أخبرنا بالسيارة والتواريخ والمكان — نحضرها ونسلّمك المفاتيح. اتصل أو راسلنا عبر واتساب.',
            ],
            'about' => [
                'seo_title' => 'من نحن — شركة تأجير سيارات موثوقة في باكو | Carclub.az',
                'seo_desc'  => 'تعرف على شركتنا الموثوقة والخبيرة في مجال تأجير السيارات في باكو. رضا العملاء والشروط المرنة من أولوياتنا.',
                'title'     => 'معلومات عنا',
                'lead'      => 'نقل موثوق بأفضل الشروط',
                'text'      => "<p>في الوقت الحاضر، لا يمكنك أن تتخيل حياتك بدون سيارة. وإذا كنا نتحدث عن رحلة إلى الخارج، ورؤية الأماكن الجميلة والطبيعة — فإن أول شيء نفكر فيه هو السيارة. وهنا تأتي شركة Carclub لمساعدتكم. سنساعدك على حل هذه المسألة بأفضل الشروط وبطريقة مختلفة عن الشركات الأخرى.</p>\n<p>سوف نقدم لك رحلة مريحة، دون طرح أي أسئلة ودون التزام. هل تريد استئجار سيارة جديدة ومريحة ومؤمنة بالكامل؟ نادي السيارات في انتظاركم. ستساعدك العشرات من المراجعات الإيجابية من عملائنا على معرفة المزيد عن عملنا.</p>",
            ],
            'cars' => [
                'seo_title' => 'السيارات — مجموعة واسعة من السيارات للإيجار في باكو | Carclub.az',
                'seo_desc'  => 'اكتشف أسطولنا من السيارات المتاحة للإيجار في باكو. سيارات حديثة ومريحة تناسب جميع الاحتياجات والميزانيات.',
                'title'     => 'السيارات',
                'lead'      => 'اختر سيارة من الأسطول — جميعها حديثة ومُصانة ومؤمّنة بالكامل. التوصيل داخل باكو مجاني.',
            ],
            'contact' => [
                'seo_title' => 'اتصل بنا — دعم سريع لتأجير السيارات في باكو | Carclub.az',
                'seo_desc'  => 'تواصل معنا لأي استفسار حول تأجير السيارات في باكو. الهاتف والبريد الإلكتروني ونموذج التواصل متاحة لراحتك.',
                'title'     => 'اتصل بنا',
                'lead'      => 'اتصل بنا أو راسلنا عبر واتساب أو أرسل طلباً — نرد بسرعة وعلى مدار الساعة.',
            ],
            'footer_text' => 'تأجير السيارات في باكو: سيارات جديدة ومؤمّنة بالكامل، توصيل مجاني إلى المطار أو إلى عنوانك، ودعم على مدار الساعة.',
        ],
    ],
];
