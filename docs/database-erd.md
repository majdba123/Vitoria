# Vitoria Database ERD

هذا الملف يوضح البنية الحالية لقاعدة البيانات كما هي موجودة فعليًا في المشروع بعد آخر التعديلات.

## 1. Core Business ERD

```mermaid
erDiagram
    USERS ||--o| VENDORS : has_one
    USERS ||--o| SYNDICATES : has_one
    CITIES ||--o{ USERS : has_many
    CITIES ||--o{ VENDORS : has_many

    CATEGORIES ||--o{ SUBCATEGORIES : has_many
    CATEGORIES ||--o{ PRODUCTS : has_many
    SUBCATEGORIES ||--o{ PRODUCTS : has_many
    VENDORS ||--o{ PRODUCTS : has_many
    PRODUCTS ||--o{ PRODUCT_PHOTOS : has_many
    PRODUCTS ||--o| SHARED_PRODUCT_DETAILS : has_one
    SHARED_PRODUCT_DETAILS ||--o| AGRICULTURAL_PRODUCT_DETAILS : has_one
    SHARED_PRODUCT_DETAILS ||--o| VETERINARY_PRODUCT_DETAILS : has_one

    USERS ||--o{ FAVOURITES : has_many
    PRODUCTS ||--o{ FAVOURITES : has_many

    USERS ||--o{ PRODUCT_REVIEWS : has_many
    PRODUCTS ||--o{ PRODUCT_REVIEWS : has_many

    USERS ||--o{ ORDERS : has_many
    VENDORS ||--o{ ORDERS : has_many
    COUPONS ||--o{ ORDERS : has_many
    ORDERS ||--o{ ORDER_ITEMS : has_many
    PRODUCTS ||--o{ ORDER_ITEMS : has_many

    CATEGORIES ||--o{ CATEGORY_VENDOR : has_many
    VENDORS ||--o{ CATEGORY_VENDOR : has_many

    USERS ||--o{ CONTACT_MESSAGES : has_many

    USERS ||--o{ ADMIN_NOTIFICATIONS : sent_by
    ADMIN_NOTIFICATIONS ||--o{ ADMIN_NOTIFICATION_RECIPIENTS : has_many
    USERS ||--o{ ADMIN_NOTIFICATION_RECIPIENTS : receives
    ADMIN_NOTIFICATIONS ||--o{ ADMIN_NOTIFICATION_READS : has_many
    USERS ||--o{ ADMIN_NOTIFICATION_READS : reads

    MANUFACTURERS ||--o{ BRANDS : has_many

    USERS {
        bigint id PK
        string name
        string phone_number UK
        string national_id UK
        tinyint type
        string email
        string avatar
        bigint city_id FK
        decimal latitude
        decimal longitude
        string timezone
        string locale
        string preferred_product_type
        tinyint age
        string membership_number UK
        timestamp email_verified_at
        string password
        string remember_token
        timestamps
    }

    VENDORS {
        bigint id PK
        bigint user_id FK
        string store_name
        string business_type
        text description
        string address
        bigint city_id FK
        decimal latitude
        decimal longitude
        string logo
        boolean is_active
        string status
        string registration_source
        string commercial_register_file
        decimal paid_amount
        timestamps
    }

    CATEGORIES {
        bigint id PK
        string name
        string type
        string logo
        string icon
        string icon_class
        decimal commission
        timestamps
    }

    SUBCATEGORIES {
        bigint id PK
        bigint category_id FK
        string name_ar
        string name_en
        timestamps
    }

    PRODUCTS {
        bigint id PK
        bigint vendor_id FK
        bigint category_id FK
        bigint subcategory_id FK
        string name
        string name_ar
        string name_en
        text description
        decimal price
        decimal discount_percentage
        int quantity
        boolean is_active
        boolean discount_is_active
        timestamp discount_starts_at
        timestamp discount_ends_at
        string discount_status
        string status
        text rejection_reason
        timestamps
    }

    PRODUCT_PHOTOS {
        bigint id PK
        bigint product_id FK
        string path
        string image_type
        int sort_order
        boolean is_primary
        timestamps
    }

    SHARED_PRODUCT_DETAILS {
        bigint id PK
        bigint product_id FK UK
        string commercial_name
        json aliases
        json barcodes
        string sku
        string manufacturer_name_ar
        string manufacturer_name_en
        string brand_name_ar
        string brand_name_en
        string country_of_origin
        string registration_number
        string registration_status
        decimal package_size
        string package_unit
        text short_description
        longtext approved_description
        json keywords
        timestamp deleted_at
        timestamps
    }

    AGRICULTURAL_PRODUCT_DETAILS {
        bigint id PK
        bigint shared_product_detail_id FK UK
        string agricultural_product_type
        json active_ingredients
        string formulation
        json target_crops
        json approved_uses
        json application_methods
        json application_rates
        string max_applications
        string application_interval_days
        json storage_conditions
        json warnings
        json ppe_requirements
        json first_aid
        text container_disposal
        json compatibility
        string pesticide_type
        string chemical_group
        text mode_of_action
        json target_pests
        json pre_harvest_intervals
        int re_entry_interval_hours
        string toxicity_class
        json environmental_hazards
        text resistance_management
        string fertilizer_type
        decimal nutrient_n_percent
        decimal nutrient_p_percent
        decimal nutrient_k_percent
        json micronutrients
        decimal organic_matter_percent
        string ph_value
        string solubility
        json growth_stages
        json fertilization_methods
        string crop_name_ar
        string crop_name_en
        string variety_name
        string variety_type
        decimal germination_percent
        decimal purity_percent
        json seed_treatment
        json disease_resistance
        json planting_windows
        json seeding_rate
        json planting_depth
        json plant_spacing
        string maturity_days
        json expected_yield
        timestamps
    }

    VETERINARY_PRODUCT_DETAILS {
        bigint id PK
        bigint shared_product_detail_id FK UK
        json active_ingredients
        string concentration
        string dosage_form
        json routes_of_administration
        json target_species
        json indications
        json dosage_instructions
        string treatment_duration
        json contraindications
        json warnings
        text special_precautions
        json adverse_reactions
        json drug_interactions
        text pregnancy_lactation_use
        int withdrawal_meat_days
        int withdrawal_milk_days
        int withdrawal_eggs_days
        json storage_conditions
        string shelf_life_after_opening
        text overdose_information
        text disposal_instructions
        text medical_disclaimer
        timestamps
    }

    COUPONS {
        bigint id PK
        string code UK
        string title
        text description
        string discount_type
        decimal discount_value
        timestamp starts_at
        timestamp ends_at
        boolean is_active
        string status
        int usage_limit
        int used_count
        bigint created_by_user_id FK
        timestamps
    }

    ORDERS {
        bigint id PK
        string order_number UK
        bigint user_id FK
        bigint vendor_id FK
        bigint coupon_id FK
        string coupon_code
        string coupon_type
        decimal coupon_value
        string status
        string payment_way
        int items_count
        decimal subtotal_amount
        decimal coupon_discount_amount
        decimal total_amount
        timestamps
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        string product_name
        decimal original_unit_price
        boolean has_discount
        decimal applied_discount_percentage
        decimal unit_price
        int quantity
        decimal line_total
        decimal discount_amount
        timestamps
    }

    FAVOURITES {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        timestamps
    }

    PRODUCT_REVIEWS {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        tinyint rating
        text body
        timestamps
    }

    CATEGORY_VENDOR {
        bigint id PK
        bigint category_id FK
        bigint vendor_id FK
        timestamps
    }

    CITIES {
        bigint id PK
        string name
        timestamps
    }

    SYNDICATES {
        bigint id PK
        bigint user_id FK UK
        string name
        string type
        string phone
        string email
        string status
        string logo
        timestamps
    }

    CONTACT_MESSAGES {
        bigint id PK
        bigint user_id FK
        string name
        string email
        text message
        string status
        text admin_reply
        timestamp replied_at
        timestamps
    }

    ADMIN_NOTIFICATIONS {
        bigint id PK
        string title
        text body
        string type
        string action_type
        bigint action_id
        bigint sent_by FK
        timestamps
    }

    ADMIN_NOTIFICATION_RECIPIENTS {
        bigint id PK
        bigint admin_notification_id FK
        bigint user_id FK
        timestamps
    }

    ADMIN_NOTIFICATION_READS {
        bigint id PK
        bigint user_id FK
        bigint admin_notification_id FK
        timestamp read_at
        timestamps
    }

    FOOTER_SETTINGS {
        bigint id PK
        text about_description
        string facebook_url
        string instagram_url
        string twitter_url
        string contact_email
        string contact_address
        string default_timezone
        timestamps
    }

    MANUFACTURERS {
        bigint id PK
        string name
        string name_ar
        string name_en
        string country
        string website
        string status
        timestamps
    }

    BRANDS {
        bigint id PK
        bigint manufacturer_id FK
        string name
        string name_ar
        string name_en
        string status
        timestamps
    }
```

## 2. Framework / Auth / Queue Tables

```mermaid
erDiagram
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : owns
    USERS ||--o{ SESSIONS : may_have
    USERS ||--o{ PASSWORD_RESET_TOKENS : may_request

    USERS {
        bigint id PK
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        morphs tokenable
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamps
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        bigint user_id
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        int expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        int expiration
    }

    JOBS {
        bigint id PK
        string queue
        longtext payload
        tinyint attempts
        int reserved_at
        int available_at
        int created_at
    }

    JOB_BATCHES {
        string id PK
        string name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options
        int cancelled_at
        int created_at
        int finished_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }
```

## 3. Relationship Notes

- `products` هو الجدول الأساسي للعرض والبيع والفرز العام.
- `shared_product_details` يحمل البيانات المشتركة بين كل أنواع المنتجات.
- `agricultural_product_details` يظهر فقط إذا كان المنتج زراعيًا.
- `veterinary_product_details` يظهر فقط إذا كان المنتج بيطريًا.
- `product_photos` هو المصدر الوحيد لصور المنتج حاليًا.
- `subcategories` تتبع `categories` بعلاقة `one-to-many`.
- `products.subcategory_id` اختياري `nullable` حتى يمكن وجود منتجات قديمة بلا تصنيف فرعي.
- `manufacturers` و `brands` موجودان كجداول مرجعية، لكن تدفق المنتج الحالي يعتمد على `manufacturer_name_ar/en` و `brand_name_ar/en` داخل `shared_product_details` بدل الربط المباشر بـ IDs.

## 4. Data Dictionary

### users
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string nullable | اسم المستخدم |
| phone_number | string unique | رقم الهاتف |
| national_id | string unique | الرقم الوطني |
| type | unsignedTinyInteger | 0 user, 1 admin, 2 vendor, 3 syndicate, 4 employee |
| email | string nullable unique | البريد |
| avatar | string nullable | صورة المستخدم |
| city_id | foreignId nullable | إلى `cities.id` |
| latitude | decimal(10,8) nullable | الموقع |
| longitude | decimal(11,8) nullable | الموقع |
| timezone | string(64) nullable | المنطقة الزمنية |
| locale | string(2) nullable | اللغة |
| preferred_product_type | string(32) nullable | تفضيل زراعي أو بيطري |
| age | unsignedTinyInteger nullable | العمر |
| membership_number | string unique nullable | رقم العضوية |
| email_verified_at | timestamp nullable | توثيق البريد |
| password | string nullable | كلمة المرور |
| remember_token | string nullable | Remember me |
| created_at / updated_at | timestamps | قياسي |

### vendors
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | foreignId | إلى `users.id` |
| store_name | string | اسم المتجر |
| business_type | string | `agriculture`, `veterinary`, `both` |
| description | text nullable | وصف |
| address | string nullable | عنوان |
| city_id | foreignId nullable | إلى `cities.id` |
| latitude | decimal(10,8) nullable | الموقع |
| longitude | decimal(11,8) nullable | الموقع |
| logo | string nullable | شعار المتجر |
| is_active | boolean | تفعيل عام |
| status | string(20) | `pending`, `active`, `inactive` |
| registration_source | string(20) | `admin` أو `self` |
| commercial_register_file | string nullable | ملف السجل التجاري |
| paid_amount | decimal(12,2) | المدفوع |
| created_at / updated_at | timestamps | قياسي |

### categories
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | اسم التصنيف |
| type | string | `agriculture` أو `veterinary` |
| logo | string nullable | صورة/شعار |
| icon | string nullable | أيقونة قديمة |
| icon_class | string nullable | CSS icon class |
| commission | decimal(5,2) | عمولة التصنيف |
| created_at / updated_at | timestamps | قياسي |

### subcategories
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| category_id | foreignId | إلى `categories.id` |
| name_ar | string | الاسم العربي |
| name_en | string | الاسم الإنجليزي |
| created_at / updated_at | timestamps | قياسي |

### products
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| vendor_id | foreignId | إلى `vendors.id` |
| category_id | foreignId | إلى `categories.id` |
| subcategory_id | foreignId nullable | إلى `subcategories.id` |
| name | string | الاسم legacy/localized accessor |
| name_ar | string nullable | الاسم العربي |
| name_en | string nullable | الاسم الإنجليزي |
| description | text nullable | الوصف |
| price | decimal(10,2) | السعر |
| discount_percentage | decimal(5,2) nullable | نسبة الخصم |
| quantity | unsignedInteger | الكمية |
| is_active | boolean | تفعيل |
| discount_is_active | boolean | تشغيل الخصم |
| discount_starts_at | timestamp nullable | بداية الخصم |
| discount_ends_at | timestamp nullable | نهاية الخصم |
| discount_status | string(20) | `pending`, `active`, `expired` |
| status | string | `pending`, `approved`, `rejected` |
| rejection_reason | text nullable | سبب الرفض |
| created_at / updated_at | timestamps | قياسي |

### product_photos
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| product_id | foreignId | إلى `products.id` |
| path | string | مسار الصورة |
| image_type | string(20) | `front` أو `back` |
| sort_order | unsignedInteger | ترتيب العرض |
| is_primary | boolean | الصورة الرئيسية |
| created_at / updated_at | timestamps | قياسي |

### shared_product_details
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| product_id | foreignId unique | إلى `products.id` |
| commercial_name | string nullable | الاسم التجاري |
| aliases | json nullable | أسماء بديلة |
| barcodes | json nullable | مصفوفة باركود |
| sku | string nullable | SKU |
| manufacturer_name_ar | string nullable | الشركة المصنعة عربي |
| manufacturer_name_en | string nullable | الشركة المصنعة إنجليزي |
| brand_name_ar | string nullable | العلامة عربي |
| brand_name_en | string nullable | العلامة إنجليزي |
| country_of_origin | string nullable | بلد المنشأ |
| registration_number | string nullable | رقم التسجيل |
| registration_status | string nullable | حالة التسجيل |
| package_size | decimal(12,2) nullable | حجم العبوة |
| package_unit | string nullable | وحدة العبوة |
| short_description | text nullable | وصف مختصر |
| approved_description | longText nullable | وصف معتمد |
| keywords | json nullable | كلمات مفتاحية |
| deleted_at | timestamp nullable | soft-delete style flag |
| created_at / updated_at | timestamps | قياسي |

### agricultural_product_details
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| shared_product_detail_id | foreignId unique | إلى `shared_product_details.id` |
| agricultural_product_type | string nullable | مثل `pesticide`, `fertilizer`, `seed`, `other` |
| active_ingredients | json nullable | المواد الفعالة |
| formulation | string nullable | الصيغة |
| target_crops | json nullable | المحاصيل المستهدفة |
| approved_uses | json nullable | الاستخدامات المعتمدة |
| application_methods | json nullable | طرق الاستخدام |
| application_rates | json nullable | معدلات الاستخدام |
| max_applications | string nullable | الحد الأقصى للتطبيق |
| application_interval_days | string nullable | الفاصل بين التطبيقات |
| storage_conditions | json nullable | شروط التخزين |
| warnings | json nullable | التحذيرات |
| ppe_requirements | json nullable | معدات الوقاية |
| first_aid | json nullable | الإسعافات الأولية |
| container_disposal | text nullable | التخلص من العبوة |
| compatibility | json nullable | التوافق |
| pesticide_type | string nullable | نوع المبيد |
| chemical_group | string nullable | المجموعة الكيميائية |
| mode_of_action | text nullable | آلية التأثير |
| target_pests | json nullable | الآفات المستهدفة |
| pre_harvest_intervals | json nullable | فترات ما قبل الحصاد |
| re_entry_interval_hours | unsignedInteger nullable | فترة إعادة الدخول |
| toxicity_class | string nullable | السمية |
| environmental_hazards | json nullable | المخاطر البيئية |
| resistance_management | text nullable | إدارة المقاومة |
| fertilizer_type | string nullable | نوع السماد |
| nutrient_n_percent | decimal(8,2) nullable | نسبة N |
| nutrient_p_percent | decimal(8,2) nullable | نسبة P |
| nutrient_k_percent | decimal(8,2) nullable | نسبة K |
| micronutrients | json nullable | العناصر الصغرى |
| organic_matter_percent | decimal(8,2) nullable | المادة العضوية |
| ph_value | string nullable | PH |
| solubility | string nullable | الذوبانية |
| growth_stages | json nullable | مراحل النمو |
| fertilization_methods | json nullable | طرق التسميد |
| crop_name_ar | string nullable | اسم المحصول عربي |
| crop_name_en | string nullable | اسم المحصول إنجليزي |
| variety_name | string nullable | الصنف |
| variety_type | string nullable | نوع الصنف |
| germination_percent | decimal(8,2) nullable | الإنبات |
| purity_percent | decimal(8,2) nullable | النقاوة |
| seed_treatment | json nullable | معالجات البذور |
| disease_resistance | json nullable | مقاومة الأمراض |
| planting_windows | json nullable | نوافذ الزراعة |
| seeding_rate | json nullable | معدل البذار |
| planting_depth | json nullable | عمق الزراعة |
| plant_spacing | json nullable | المسافات |
| maturity_days | string nullable | أيام النضج |
| expected_yield | json nullable | الإنتاج المتوقع |
| created_at / updated_at | timestamps | قياسي |

### veterinary_product_details
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| shared_product_detail_id | foreignId unique | إلى `shared_product_details.id` |
| active_ingredients | json nullable | المواد الفعالة |
| concentration | string nullable | التركيز |
| dosage_form | string nullable | الشكل الدوائي |
| routes_of_administration | json nullable | طرق الإعطاء |
| target_species | json nullable | الأنواع المستهدفة |
| indications | json nullable | دواعي الاستعمال |
| dosage_instructions | json nullable | تعليمات الجرعة |
| treatment_duration | string nullable | مدة العلاج |
| contraindications | json nullable | موانع الاستعمال |
| warnings | json nullable | تحذيرات |
| special_precautions | text nullable | احتياطات خاصة |
| adverse_reactions | json nullable | آثار جانبية |
| drug_interactions | json nullable | تداخلات دوائية |
| pregnancy_lactation_use | text nullable | الحمل والإرضاع |
| withdrawal_meat_days | unsignedInteger nullable | سحب اللحم |
| withdrawal_milk_days | unsignedInteger nullable | سحب الحليب |
| withdrawal_eggs_days | unsignedInteger nullable | سحب البيض |
| storage_conditions | json nullable | التخزين |
| shelf_life_after_opening | string nullable | الصلاحية بعد الفتح |
| overdose_information | text nullable | الجرعة الزائدة |
| disposal_instructions | text nullable | التخلص |
| medical_disclaimer | text nullable | التنبيه الطبي |
| created_at / updated_at | timestamps | قياسي |

### coupons
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| code | string unique | الكود |
| title | string | العنوان |
| description | text nullable | الوصف |
| discount_type | string(20) | غالبًا `percentage` |
| discount_value | decimal(10,2) | القيمة |
| starts_at | timestamp nullable | البداية |
| ends_at | timestamp nullable | النهاية |
| is_active | boolean | تفعيل |
| status | string(20) | `pending`, `active`, `expired` |
| usage_limit | unsignedInteger nullable | حد الاستخدام |
| used_count | unsignedInteger | عدد الاستخدامات |
| created_by_user_id | foreignId nullable | إلى `users.id` |
| created_at / updated_at | timestamps | قياسي |

### orders
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| order_number | string unique | رقم الطلب |
| user_id | foreignId | إلى `users.id` |
| vendor_id | foreignId | إلى `vendors.id` |
| coupon_id | foreignId nullable | إلى `coupons.id` |
| coupon_code | string nullable | Snapshot |
| coupon_type | string(20) nullable | Snapshot |
| coupon_value | decimal(10,2) nullable | Snapshot |
| status | string | حالة الطلب |
| payment_way | string(20) | وسيلة الدفع |
| items_count | unsignedInteger | عدد العناصر |
| subtotal_amount | decimal(12,2) | الإجمالي قبل الخصم |
| coupon_discount_amount | decimal(12,2) | خصم الكوبون |
| total_amount | decimal(12,2) | الإجمالي النهائي |
| created_at / updated_at | timestamps | قياسي |

### order_items
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| order_id | foreignId | إلى `orders.id` |
| product_id | foreignId nullable | إلى `products.id` |
| product_name | string | Snapshot |
| original_unit_price | decimal(12,2) | قبل الخصم |
| has_discount | boolean | هل يوجد خصم |
| applied_discount_percentage | decimal(8,2) nullable | النسبة |
| unit_price | decimal(12,2) | السعر الفعلي |
| quantity | unsignedInteger | الكمية |
| line_total | decimal(12,2) | الإجمالي |
| discount_amount | decimal(12,2) | قيمة الخصم |
| created_at / updated_at | timestamps | قياسي |

### favourites
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | foreignId | إلى `users.id` |
| product_id | foreignId | إلى `products.id` |
| created_at / updated_at | timestamps | unique on `(user_id, product_id)` |

### product_reviews
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| product_id | foreignId | إلى `products.id` |
| user_id | foreignId | إلى `users.id` |
| rating | unsignedTinyInteger | 1 إلى 5 |
| body | text nullable | نص المراجعة |
| created_at / updated_at | timestamps | يوجد migration لاحق أزال uniqueness القديمة |

### category_vendor
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| category_id | foreignId | إلى `categories.id` |
| vendor_id | foreignId | إلى `vendors.id` |
| created_at / updated_at | timestamps | unique on `(category_id, vendor_id)` |

### cities
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | اسم المدينة |
| created_at / updated_at | timestamps | قياسي |

### syndicates
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | foreignId unique | إلى `users.id` |
| name | string | الاسم |
| type | string(20) | `agriculture` أو `veterinary` |
| phone | string nullable | الهاتف |
| email | string nullable | البريد |
| status | string(20) | `active` أو `inactive` |
| logo | string nullable | الشعار |
| created_at / updated_at | timestamps | قياسي |

### contact_messages
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | foreignId nullable | إلى `users.id` |
| name | string nullable | اسم مرسل غير مسجل |
| email | string | البريد |
| message | text | الرسالة |
| status | string | حالة المعالجة |
| admin_reply | text nullable | رد الإدارة |
| replied_at | timestamp nullable | وقت الرد |
| created_at / updated_at | timestamps | قياسي |

### admin_notifications
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| title | string | العنوان |
| body | text | النص |
| type | string(20) | `public` أو `private` |
| action_type | string(32) nullable | مثال `product`, `order` |
| action_id | unsignedBigInteger nullable | رقم السجل المرتبط |
| sent_by | foreignId nullable | إلى `users.id` |
| created_at / updated_at | timestamps | قياسي |

### admin_notification_recipients
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| admin_notification_id | foreignId | إلى `admin_notifications.id` |
| user_id | foreignId | إلى `users.id` |
| created_at / updated_at | timestamps | unique on notification+user |

### admin_notification_reads
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| user_id | foreignId | إلى `users.id` |
| admin_notification_id | foreignId | إلى `admin_notifications.id` |
| read_at | timestamp | وقت القراءة |
| created_at / updated_at | timestamps | unique on user+notification |

### footer_settings
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| about_description | text nullable | وصف الفوتر |
| facebook_url | string(500) nullable | رابط |
| instagram_url | string(500) nullable | رابط |
| twitter_url | string(500) nullable | رابط |
| contact_email | string(255) nullable | بريد |
| contact_address | string(500) nullable | عنوان |
| default_timezone | string(64) nullable | توقيت افتراضي |
| created_at / updated_at | timestamps | قياسي |

### manufacturers
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | الاسم العام |
| name_ar | string nullable | عربي |
| name_en | string nullable | إنجليزي |
| country | string nullable | الدولة |
| website | string nullable | الموقع |
| status | string | الحالة |
| created_at / updated_at | timestamps | قياسي |

### brands
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| manufacturer_id | foreignId nullable | إلى `manufacturers.id` |
| name | string | الاسم العام |
| name_ar | string nullable | عربي |
| name_en | string nullable | إنجليزي |
| status | string | الحالة |
| created_at / updated_at | timestamps | قياسي |

### personal_access_tokens
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| tokenable_type | string | Morph |
| tokenable_id | bigint | Morph |
| name | string | اسم التوكن |
| token | string unique | قيمة التوكن |
| abilities | text nullable | الصلاحيات |
| last_used_at | timestamp nullable | آخر استخدام |
| expires_at | timestamp nullable | الانتهاء |
| created_at / updated_at | timestamps | قياسي |

### password_reset_tokens
| Column | Type | Notes |
|---|---|---|
| email | string | PK |
| token | string | توكن |
| created_at | timestamp nullable | تاريخ الإنشاء |

### sessions
| Column | Type | Notes |
|---|---|---|
| id | string | PK |
| user_id | foreignId nullable | مستخدم الجلسة |
| ip_address | string(45) nullable | IP |
| user_agent | text nullable | المتصفح |
| payload | longText | بيانات الجلسة |
| last_activity | integer | آخر نشاط |

### cache
| Column | Type | Notes |
|---|---|---|
| key | string | PK |
| value | mediumText | القيمة |
| expiration | integer | وقت الانتهاء |

### cache_locks
| Column | Type | Notes |
|---|---|---|
| key | string | PK |
| owner | string | مالك القفل |
| expiration | integer | وقت الانتهاء |

### jobs
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| queue | string | اسم الطابور |
| payload | longText | الحمولة |
| attempts | unsignedTinyInteger | المحاولات |
| reserved_at | unsignedInteger nullable | الحجز |
| available_at | unsignedInteger | متاح من |
| created_at | unsignedInteger | الإنشاء |

### job_batches
| Column | Type | Notes |
|---|---|---|
| id | string | PK |
| name | string | الاسم |
| total_jobs | integer | الإجمالي |
| pending_jobs | integer | المعلقة |
| failed_jobs | integer | الفاشلة |
| failed_job_ids | longText | IDs فاشلة |
| options | mediumText nullable | خيارات |
| cancelled_at | integer nullable | الإلغاء |
| created_at | integer | الإنشاء |
| finished_at | integer nullable | الانتهاء |

### failed_jobs
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| uuid | string unique | UUID |
| connection | text | الاتصال |
| queue | text | الطابور |
| payload | longText | الحمولة |
| exception | longText | الخطأ |
| failed_at | timestamp | وقت الفشل |

## 5. Important Implementation Notes

- المنتج يمر من `products` ثم `shared_product_details`.
- لو كان التصنيف `agriculture` يمكن أن يوجد سجل واحد فقط في `agricultural_product_details`.
- لو كان التصنيف `veterinary` يمكن أن يوجد سجل واحد فقط في `veterinary_product_details`.
- صور المنتج لا تخزن داخل `products` بل فقط داخل `product_photos`.
- الباركود لا يخزن كحقل منفرد، بل فقط كمصفوفة `barcodes` داخل `shared_product_details`.
- `brands` و `manufacturers` ما زالا موجودين ككيانات قاعدة بيانات، لكن المنتج الحالي لا يعتمد عليهما بعلاقات مباشرة.
