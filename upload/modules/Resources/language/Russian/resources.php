<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Russian Language for Resources module
 */

$language = array(
	/*
	 *  Resources
	 */ 
	'resources' => 'Ресурсы',
	'categories' => 'Категории',
	'no_resources' => 'Еще ни одного ресурса не было добавлено.',
	'new_resource' => 'Создать ресурс',
	'select_category' => 'Выберите категорию',
	'github_username' => 'Имя пользователя GitHub',
	'github_repo_name' => 'Название репозитория GitHub',
	'link_to_github_repo' => 'Ссылка на репозиторй GitHub',
	'required' => 'Обязательно',
	'resource_name' => 'Название',
	'resource_description' => 'Описание',
	'version_tag' => 'Тег версия',
	'version_tag_help' => 'Это должно соответствовать вашему тегу релиза GitHub',
	'contributors' => 'Участники',
	'name_required' => 'Пожалуйста, введите название ресурса',
	'content_required' => 'Пожалуйста, введите описание ресурса',
	'github_username_required' => 'Пожалуйста, введите ваше имя пользователя GitHub',
	'github_repo_required' => 'Пожалуйста, введите название репозиторя GitHub',
	'version_tag_required' => 'Пожалуйста, введите версию для вашего ресурса',
	'category_required' => 'Пожалуйста, выберите категорию для вашего ресурса',
	'name_min_2' => 'Название ресурса может состоять минимум из 2-х символов',
	'content_min_2' => 'Описание ресурса может состоять минимум из 2-х символов',
	'github_username_min_2' => 'Ваше имя пользователя GitHub может состоять минимум из 2-х символов',
	'github_repo_min_2' => 'Ваше название репозитория GitHub может состоять минимум их 2-х символов',
	'name_max_64' => 'Ваше название ресурса может состоять максимум из 64 символов',
	'content_max_20000' => 'Ваше описание ресурса может состоять максимум из 20000 символов',
	'github_username_max_32' => 'Ваше имя пользователя GitHub может состоять максимум из 32 символов',
	'github_repo_max_64' => 'Ваше название репозитория GitHub может состоять максимум из 64 символов',
	'version_max_16' => 'Тег версии может состоять максимум из 16 символов',
	'contributors_max_255' => 'Список участников может состоять максимум из 255 символов',
	'unable_to_get_repo' => 'Не удалось получить последнюю информацию о релизе от {x}. Вы создали релиз на GitHub?',
	'update_already_exists' => 'Обновление с этим тегом версии уже существует!',
	'select_release' => 'Выберите релиз:',
	'resource' => 'Ресурс',
	'stats' => 'Статистика',
	'author' => 'Автор',
	'x_views' => 'Просмотров: {x}', // Don't replace {x}
	'x_downloads' => 'Скачиваний: {x}', // Don't replace {x}
	'in_category_x' => 'в категории «{x}»', // Don't replace {x}
	'viewing_resource_x' => 'Просмотр ресурса «{x}»', // Don't replace {x}
	'resource_index' => 'Индекс ресурса',
	'reviews' => 'Отзывы',
	'view_other_resources' => 'Просмотр других ресурсов пользователя {x}', // Don't replace {x}
	'download' => 'Скачать',
	'other_releases' => 'Другие релизы',
	'no_reviews' => 'Нет отзывов',
	'new_review' => 'Создать отзыв',
	'update' => 'Обновить ресурс',
	'updated_x' => 'обновлено {x}', // Don't replace {x}
	'viewing_all_releases' => 'Просмотр всех релизов ресурса «{x}»', // Don't replace {x}
	'viewing_release' => 'Просмотр релизов {x} ресурса {y}', // Don't replace {x} or {y}
    'editing_resource' => 'Редактирование ресурса',
    'contributors_x' => 'Участники: {x}', // Don't replace {x}
    'move_resource' => 'Переместить ресурс',
    'delete_resource' => 'Удалить ресурс',
    'confirm_delete_resource' => 'Вы уверены, что хотите удалить ресурс «{x}»?', // Don't replace {x}
    'invalid_category' => 'Вы выбрали недопустимую категорию',
    'move_to' => 'Переместить ресурс в:',
    'no_categories_available' => 'Нет ни одной категории, куда можно переместить этот ресурс!',
    'delete_review' => 'Удалить отзыв',
    'confirm_delete_review' => 'Вы уверены, что хотите удалить этот отзыв?',
    'viewing_resources_by_x' => 'Просмотр ресурсов пользователя {x}', // Don't replace {x}
	'release_type' => 'Тип релиза',
    'zip_file' => 'Zip-файл',
    'github_release' => 'GitHub релиз',
    'type' => 'Тип',
    'free_resource' => 'Бесплатный ресурс',
    'premium_resource' => 'Премиум ресурс',
    'price' => 'Цена',
    'invalid_price' => 'Неверная цена.',
    'paypal_email_address' => 'PayPal адрес электронной почты',
    'paypal_email_address_info' => 'Это адрес электронной почты PayPal, на который будут отправляться деньги, когда кто-то покупает ваши премиум ресурсы.',
    'invalid_email_address' => 'Пожалуйста, введите действительный адрес электронной почты PayPal, от 4 до 64 символов.',
    'no_payment_email' => 'Нет никакого адреса электронной почты PayPal, связанного с вашим аккаунтом. Вы можете добавить его позже в UserCP.',
    'my_resources' => 'Мои ресурсы',
    'purchased_resources' => 'Приобретенные ресурсы',
    'no_purchased_resources' => 'Сейчас у вас нет приобретенных ресурсов.',
    'choose_file' => 'Выберите файл',
    'zip_only' => 'Тольйо .zip файлы поддерживаются',
    'file_not_zip' => 'Файл не является .zip!',
    'filesize_max_x' => 'Размер файла должен быть не более {x}кб', // Don't replace {x}, unit kilobytes
	'file_upload_failed' => 'Загрузка файла не завершилась. Ошибка: {x}', // Don't replace {x}
	'purchase_for_x' => 'Покупка {x}', // Don't replace {x}
	'purchase' => 'Купить',
    'purchasing_resource_x' => 'Покупка {x}', // Don't replace {x}
	'payment_pending' => 'Payment Pending',
    'update_title' => 'Update Title',
    'update_information' => 'Обновить информацию',
	'paypal_not_configured' => 'Интеграция PayPal еще не настроена! Пожалуйста, свяжитесь с администратором.',
	'error_while_purchasing' => 'Прости! При покупке этого ресурса произошла ошибка. Пожалуйста, свяжитесь с администратором.',
	'author_doesnt_have_paypal' => 'Прости! Автор ресурса еще не подключил свой аккаунт PayPal.',
	'sorry_please_try_again' => 'Прости! Возникла проблема, пожалуйста, попробуйте еще раз.',
    'purchase_cancelled' => 'Покупка была успешно отменена.',
    'purchase_complete' => 'Покупка прошла успешно. Обратите внимание, что ресурс станет доступен для скачивания только после того, как оплата будет полностью завершена.',
    'log_in_to_download' => 'Войдите, чтобы скачать',
	'external_download' => 'Внешнее скачивание',
    'external_link' => 'Ссылка на скачивание',
    'external_link_error' => 'Please enter a valid external link, between x and y characters long.',
    'select_release_type_error' => 'Please select a release type.',
    'sort_by' => 'Sort By',
    'last_updated' => 'Last Updated',
    'newest' => 'Newest',
    
    'total_downloads' => 'Total Downloads',
    'first_release' => 'First Release',
    'last_release' => 'Last Release',
    'views' => 'Views',
    'category' => 'Category',
    'rating' => 'Rating',
    'version_x' => 'Version {x}', // Don't replace {x}
    'release' => 'Release', // Don't replace {x}

    // Admin
    'permissions' => 'Права',
    'new_category' => '<i class="fa fa-plus-circle"></i> Новая категория',
    'creating_category' => 'Создание категории',
    'category_name' => 'Название категории',
    'category_description' => 'Описание категории',
    'input_category_title' => 'Пожалуйста, введите название категории.',
    'category_name_minimum' => 'Название категории может состоять минимум из 2-х символов.',
    'category_name_maxmimum' => 'Ваше название категории может состоять максимум из 150 символов.',
    'category_description_maximum' => 'Ваше описание категории может состоять максимум из 250 символов.',
    'category_created_successfully' => 'Категория успешно создана.',
    'category_updated_successfully' => 'Категория успешно обновлена.',
    'category_deleted_successfully' => 'Категория успешно удалена.',
    'category_permissions' => 'Права категории',
    'group' => 'Группа',
    'can_view_category' => 'Может просматривать категорию?',
    'can_post_resource' => 'Может выкладывать ресурсы?',
    'moderation' => 'Модерация',
    'can_move_resources' => 'Может перемещать ресурсы?',
    'can_edit_resources' => 'Может редактировать ресурсы?',
    'can_delete_resources' => 'Может удалять ресурсы?',
    'can_edit_reviews' => 'Может редактировать отзывы?',
    'can_delete_reviews' => 'Может удалять отзывы?',
    'can_download_resources' => 'Может скачивать ресурсы?',
    'can_post_premium_resource' => 'Может выкладывать премиум ресурсы?',
    'delete_category' => 'Удалить категорию',
    'move_resources_to' => 'Переместить ресурс в...',
    'delete_resources' => 'Удалить ресурс',
    'downloads' => 'Загрузки',
    'no_categories' => 'Еще ни одной категории не было создано.',
    'editing_category' => 'Редактирование категории',
    'settings' => 'Настройки',
    'settings_updated_successfully' => 'Настройки успешно обновлены.',
    'currency' => 'ISO-4217 валюта',
    'invalid_currency' => 'Недопустимая валюта ISO-4217! Список допустимых кодов можно найти <a href="https://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank" rel="noopener nofollow">здесь</a>',
    'upload_directory_not_writable' => 'Каталог uploads/resources недоступен для записи!',
    'maximum_filesize' => 'Максимальный размер файла (килобайты)',
    'invalid_filesize' => 'Недопустимый размер файла!',
    'pre_purchase_information' => 'Pre-purchase information',
    'invalid_pre_purchase_info' => 'Invalid pre-purchase information! Please ensure it is under 100,000 characters.',
    'paypal_api_details' => 'PayPal API Details',
    'paypal_api_details_info' => 'Значения этих полей скрыты по соображениям безопасности.<br />Если вы обновляете эти настройки, пожалуйста, введите как Client ID, так и Client Secret вместе.',
    'paypal_client_id' => 'PayPal Client ID',
    'paypal_client_secret' => 'PayPal Client Secret',
    'paypal_config_not_writable' => 'modules/Resources/paypal.php не доступен для записи для сохранения настроек PayPal.',

    // Hook
    'new_resource_text' => 'Новый ресурс создан в {x} пользователем {y}',
    'updated_resource_text' => 'Ресурс обновлен в {x} пользователем {y}'
);
