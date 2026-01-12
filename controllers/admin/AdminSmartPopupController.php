<?php
/**
 * Admin Controller for Smart Popup
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ps_advanced_popup/classes/SmartPopup.php';

class AdminSmartPopupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'smart_popup';
        $this->className = 'SmartPopup';
        $this->lang = true;
        $this->bootstrap = true;
        $this->identifier = 'id_popup';
        $this->_defaultOrderBy = 'priority';
        $this->_defaultOrderWay = 'DESC';

        parent::__construct();

        $this->fields_list = [
            'id_popup' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'title' => [
                'title' => $this->l('Title'),
                'filter_key' => 'pl!title',
            ],
            'popup_type' => [
                'title' => $this->l('Type'),
                'type' => 'select',
                'list' => $this->getPopupTypes(),
                'filter_key' => 'a!popup_type',
                'callback' => 'getPopupTypeBadge',
            ],
            'trigger_type' => [
                'title' => $this->l('Trigger'),
                'type' => 'select',
                'list' => $this->getTriggerTypes(),
                'filter_key' => 'a!trigger_type',
            ],
            'active' => [
                'title' => $this->l('Status'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
            ],
            'priority' => [
                'title' => $this->l('Priority'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ],
            'enableSelection' => [
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-power-off text-danger',
            ],
        ];

        $this->_select = 'pl.title';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'smart_popup_lang` pl
                        ON (a.id_popup = pl.id_popup AND pl.id_lang = ' . (int) $this->context->language->id . ')';
    }

    public function getPopupTypeBadge($value)
    {
        $badges = [
            'image' => '<span class="badge badge-info">' . $this->l('Image') . '</span>',
            'html' => '<span class="badge badge-success">' . $this->l('HTML') . '</span>',
            'newsletter' => '<span class="badge badge-warning">' . $this->l('Newsletter') . '</span>',
        ];
        return isset($badges[$value]) ? $badges[$value] : $value;
    }

    private function getPopupTypes()
    {
        return [
            'image' => $this->l('Image Only'),
            'html' => $this->l('HTML Content'),
            'newsletter' => $this->l('Newsletter Form'),
        ];
    }

    private function getTriggerTypes()
    {
        return [
            'load' => $this->l('On Page Load'),
            'exit' => $this->l('Exit Intent'),
            'scroll' => $this->l('Scroll Percentage'),
            'inactivity' => $this->l('Inactivity'),
        ];
    }

    private function getAnimationTypes()
    {
        return [
            'fadeIn' => 'Fade In',
            'fadeInDown' => 'Fade In Down',
            'fadeInUp' => 'Fade In Up',
            'bounceIn' => 'Bounce In',
            'zoomIn' => 'Zoom In',
            'slideInDown' => 'Slide In Down',
            'slideInUp' => 'Slide In Up',
        ];
    }

    private function getCloseButtonStyles()
    {
        return [
            'default' => $this->l('Default (X)'),
            'circle' => $this->l('Circle'),
            'square' => $this->l('Square'),
            'text' => $this->l('Text (Close)'),
        ];
    }

    public function renderForm()
    {
        $animationOptions = [];
        foreach ($this->getAnimationTypes() as $key => $value) {
            $animationOptions[] = ['id' => $key, 'name' => $value];
        }

        $closeButtonOptions = [];
        foreach ($this->getCloseButtonStyles() as $key => $value) {
            $closeButtonOptions[] = ['id' => $key, 'name' => $value];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Popup Settings'),
                'icon' => 'icon-cogs',
            ],
            'tabs' => [
                'general' => $this->l('General'),
                'design' => $this->l('Design & Content'),
                'trigger' => $this->l('Triggers'),
                'targeting' => $this->l('Targeting'),
                'frequency' => $this->l('Frequency'),
            ],
            'input' => [
                // === GENERAL TAB ===
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                    'tab' => 'general',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Title'),
                    'name' => 'title',
                    'lang' => true,
                    'required' => true,
                    'hint' => $this->l('Internal title for identification'),
                    'tab' => 'general',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Popup Type'),
                    'name' => 'popup_type',
                    'options' => [
                        'query' => [
                            ['id' => 'image', 'name' => $this->l('Image Only')],
                            ['id' => 'html', 'name' => $this->l('HTML Content')],
                            ['id' => 'newsletter', 'name' => $this->l('Newsletter Form')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'tab' => 'general',
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('Start Date'),
                    'name' => 'date_start',
                    'hint' => $this->l('Leave empty for immediate start'),
                    'tab' => 'general',
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('End Date'),
                    'name' => 'date_end',
                    'hint' => $this->l('Leave empty for no end date'),
                    'tab' => 'general',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Priority'),
                    'name' => 'priority',
                    'class' => 'fixed-width-sm',
                    'hint' => $this->l('Higher number = higher priority'),
                    'tab' => 'general',
                ],

                // === DESIGN TAB ===
                [
                    'type' => 'textarea',
                    'label' => $this->l('Content'),
                    'name' => 'content',
                    'lang' => true,
                    'autoload_rte' => true,
                    'cols' => 100,
                    'rows' => 15,
                    'desc' => $this->l('Popup içinde gösterilecek HTML içerik. Metin, resim, video veya özel HTML kodları ekleyebilirsiniz. Newsletter tipi için bu alan form üstünde açıklama metni olarak kullanılır.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('CTA Button Text'),
                    'name' => 'cta_text',
                    'lang' => true,
                    'desc' => $this->l('Aksiyon butonu metni. Örnek: "Hemen Al", "Detayları Gör", "Fırsatı Kaçırma". Newsletter tipinde "Abone Ol" butonu için kullanılır.'),
                    'placeholder' => $this->l('Örn: Hemen İncele'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('CTA Button URL'),
                    'name' => 'cta_url',
                    'lang' => true,
                    'desc' => $this->l('Butona tıklandığında yönlendirilecek sayfa adresi. Tam URL girin (https:// ile başlamalı). Newsletter tipinde kullanılmaz.'),
                    'placeholder' => 'https://example.com/kampanya',
                    'tab' => 'design',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Width (px)'),
                    'name' => 'width',
                    'class' => 'fixed-width-sm',
                    'suffix' => 'px',
                    'desc' => $this->l('Popup genişliği piksel cinsinden. Mobilde otomatik olarak ekrana sığacak şekilde küçülür. Önerilen: 400-700px arası.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'color',
                    'label' => $this->l('Background Color'),
                    'name' => 'bg_color',
                    'desc' => $this->l('Popup arka plan rengi. Arkaplan resmi kullanılıyorsa bu renk resmin altında görünür.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Background Image'),
                    'name' => 'bg_image',
                    'display_image' => true,
                    'desc' => $this->l('Opsiyonel arka plan görseli. Image tipi popup için ana görsel olarak kullanılır. Önerilen boyut: 600x400px, max 500KB.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Border Radius (px)'),
                    'name' => 'border_radius',
                    'class' => 'fixed-width-sm',
                    'suffix' => 'px',
                    'desc' => $this->l('Köşe yuvarlaklığı. 0 = keskin köşeler, 8-16 = modern görünüm, 50+ = oval kenarlar.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Overlay Opacity'),
                    'name' => 'overlay_opacity',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Arka plan karartma oranı. 0 = şeffaf (karartma yok), 0.5 = yarı karartma, 1 = tam siyah. Önerilen: 0.5'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Animation'),
                    'name' => 'animation',
                    'options' => [
                        'query' => $animationOptions,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Popup açılış animasyonu. Fade In = yumuşak belirme, Bounce = zıplama efekti, Zoom = büyüyerek açılma.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Close Button Style'),
                    'name' => 'close_button_style',
                    'options' => [
                        'query' => $closeButtonOptions,
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Kapatma butonu görünümü. Default = basit X, Circle = yuvarlak arka planlı, Text = "Kapat" yazısı.'),
                    'tab' => 'design',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Hide on Mobile'),
                    'name' => 'hide_on_mobile',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'mobile_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'mobile_off', 'value' => 0, 'label' => $this->l('No')],
                    ],
                    'desc' => $this->l('Evet seçilirse popup mobil cihazlarda gösterilmez. Google SEO kurallarına uyum için önerilir.'),
                    'tab' => 'design',
                ],

                // === TRIGGER TAB ===
                [
                    'type' => 'select',
                    'label' => $this->l('Trigger Type'),
                    'name' => 'trigger_type',
                    'options' => [
                        'query' => [
                            ['id' => 'load', 'name' => $this->l('On Page Load')],
                            ['id' => 'exit', 'name' => $this->l('Exit Intent (Desktop)')],
                            ['id' => 'scroll', 'name' => $this->l('Scroll Percentage')],
                            ['id' => 'inactivity', 'name' => $this->l('User Inactivity')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Popup\'un ne zaman gösterileceğini belirler. Page Load = sayfa yüklendikten X saniye sonra, Exit Intent = kullanıcı sayfadan çıkmaya çalışınca (sadece masaüstü), Scroll = sayfa X% kaydırılınca, Inactivity = X saniye hareketsizlik sonrası.'),
                    'tab' => 'trigger',
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Trigger Value'),
                    'name' => 'trigger_value',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->l('Tetikleyici değeri: Page Load/Inactivity için saniye (örn: 5 = 5 saniye), Scroll için yüzde (örn: 50 = sayfanın %50\'si). Exit Intent için bu değer kullanılmaz.'),
                    'tab' => 'trigger',
                ],

                // === TARGETING TAB ===
                [
                    'type' => 'select',
                    'label' => $this->l('Target Pages'),
                    'name' => 'target_pages[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'options' => [
                        'query' => [
                            ['id' => 'all', 'name' => $this->l('All Pages')],
                            ['id' => 'home', 'name' => $this->l('Homepage')],
                            ['id' => 'category', 'name' => $this->l('Category Pages')],
                            ['id' => 'product', 'name' => $this->l('Product Pages')],
                            ['id' => 'cart', 'name' => $this->l('Cart')],
                            ['id' => 'checkout', 'name' => $this->l('Checkout')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Popup\'un hangi sayfalarda gösterileceğini seçin. Boş bırakılırsa veya "All Pages" seçilirse tüm sayfalarda gösterilir. Birden fazla sayfa seçebilirsiniz.'),
                    'tab' => 'targeting',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Target Customer Groups'),
                    'name' => 'target_groups[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'options' => [
                        'query' => Group::getGroups($this->context->language->id),
                        'id' => 'id_group',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Popup\'u sadece belirli müşteri gruplarına göstermek için seçin. Boş bırakılırsa tüm ziyaretçilere gösterilir. Örnek: Sadece VIP müşterilere özel kampanya.'),
                    'tab' => 'targeting',
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Target Devices'),
                    'name' => 'target_devices[]',
                    'multiple' => true,
                    'class' => 'chosen',
                    'options' => [
                        'query' => [
                            ['id' => 'desktop', 'name' => $this->l('Desktop')],
                            ['id' => 'tablet', 'name' => $this->l('Tablet')],
                            ['id' => 'mobile', 'name' => $this->l('Mobile')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                    'desc' => $this->l('Popup\'un hangi cihazlarda gösterileceğini seçin. Boş bırakılırsa tüm cihazlarda gösterilir. Mobil için ayrı bir popup oluşturmanız önerilir.'),
                    'tab' => 'targeting',
                ],

                // === FREQUENCY TAB ===
                [
                    'type' => 'text',
                    'label' => $this->l('Show Again After (days)'),
                    'name' => 'frequency_days',
                    'class' => 'fixed-width-sm',
                    'suffix' => $this->l('days'),
                    'desc' => $this->l('Kullanıcı popup\'u kapattıktan kaç gün sonra tekrar gösterileceği. 0 = her ziyarette göster, 1 = günde bir kez, 7 = haftada bir, 30 = ayda bir. Önerilen: 1-7 gün.'),
                    'tab' => 'frequency',
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        // Load existing targeting rules for edit
        if ($this->object && $this->object->id) {
            $this->loadTargetingValues();
        }

        return parent::renderForm();
    }

    /**
     * Set default values for new popup
     */
    public function getFieldsValue($obj)
    {
        $fields = parent::getFieldsValue($obj);

        // Set defaults for new popup
        if (!$obj->id) {
            $fields['active'] = 0;
            $fields['popup_type'] = 'html';
            $fields['trigger_type'] = 'load';
            $fields['trigger_value'] = 3;
            $fields['frequency_days'] = 1;
            $fields['width'] = 600;
            $fields['bg_color'] = '#ffffff';
            $fields['border_radius'] = 8;
            $fields['overlay_opacity'] = 0.5;
            $fields['animation'] = 'fadeIn';
            $fields['close_button_style'] = 'default';
            $fields['hide_on_mobile'] = 0;
            $fields['priority'] = 0;
        }

        return $fields;
    }

    /**
     * Load targeting values for form
     */
    private function loadTargetingValues()
    {
        $rules = $this->object->getTargetingRules();

        foreach ($rules as $rule) {
            $ids = json_decode($rule['target_ids'], true);
            switch ($rule['target_type']) {
                case 'page':
                    $_POST['target_pages'] = $ids;
                    break;
                case 'customer_group':
                    $_POST['target_groups'] = $ids;
                    break;
                case 'device':
                    $_POST['target_devices'] = $ids;
                    break;
            }
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            // Handle image upload
            if (isset($_FILES['bg_image']) && $_FILES['bg_image']['size'] > 0) {
                $this->processImageUpload();
            }
        }

        $result = parent::postProcess();

        // Save targeting rules after popup is saved
        if (Tools::isSubmit('submitAdd' . $this->table) && $this->object && $this->object->id) {
            $this->processTargetingRules();
        }

        return $result;
    }

    /**
     * Process image upload
     */
    protected function processImageUpload()
    {
        $uploadDir = _PS_MODULE_DIR_ . 'ps_advanced_popup/views/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'popup_' . time() . '_' . $_FILES['bg_image']['name'];
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $filePath)) {
            $_POST['bg_image'] = 'modules/ps_advanced_popup/views/img/' . $fileName;
        }
    }

    /**
     * Process targeting rules
     */
    protected function processTargetingRules()
    {
        $rules = [];

        // Pages
        $pages = Tools::getValue('target_pages');
        if ($pages && is_array($pages) && !in_array('all', $pages)) {
            $rules[] = [
                'target_type' => 'page',
                'target_ids' => json_encode($pages),
            ];
        }

        // Customer Groups
        $groups = Tools::getValue('target_groups');
        if ($groups && is_array($groups)) {
            $rules[] = [
                'target_type' => 'customer_group',
                'target_ids' => json_encode(array_map('intval', $groups)),
            ];
        }

        // Devices
        $devices = Tools::getValue('target_devices');
        if ($devices && is_array($devices)) {
            $rules[] = [
                'target_type' => 'device',
                'target_ids' => json_encode($devices),
            ];
        }

        $this->object->saveTargetingRules($rules);
    }

    /**
     * Render stats view
     */
    public function renderStats()
    {
        $idPopup = (int) Tools::getValue('id_popup');

        if (!$idPopup) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSmartPopup'));
        }

        $popup = new SmartPopup($idPopup, $this->context->language->id);

        $stats = [
            'today' => $this->getStatsForPeriod($idPopup, 1),
            'week' => $this->getStatsForPeriod($idPopup, 7),
            'month' => $this->getStatsForPeriod($idPopup, 30),
            'all_time' => $this->getStatsForPeriod($idPopup, 365),
        ];

        $chartData = $this->getChartData($idPopup, 30);

        $this->context->smarty->assign([
            'popup' => $popup,
            'stats' => $stats,
            'chartData' => json_encode($chartData),
            'conversionRate' => $stats['all_time']['impressions'] > 0
                ? round(($stats['all_time']['conversions'] / $stats['all_time']['impressions']) * 100, 2)
                : 0,
            'back_url' => $this->context->link->getAdminLink('AdminSmartPopup'),
        ]);

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'ps_advanced_popup/views/templates/admin/stats.tpl'
        );
    }

    private function getStatsForPeriod($idPopup, $days)
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        $sql = new DbQuery();
        $sql->select('stat_type, SUM(count) as total');
        $sql->from('smart_popup_stats');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->groupBy('stat_type');

        $results = Db::getInstance()->executeS($sql);

        $stats = ['impressions' => 0, 'conversions' => 0];
        foreach ($results as $row) {
            if ($row['stat_type'] === 'impression') {
                $stats['impressions'] = (int) $row['total'];
            } else {
                $stats['conversions'] = (int) $row['total'];
            }
        }

        return $stats;
    }

    private function getChartData($idPopup, $days)
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        $sql = new DbQuery();
        $sql->select('stat_date, stat_type, count');
        $sql->from('smart_popup_stats');
        $sql->where('id_popup = ' . (int) $idPopup);
        $sql->where('stat_date >= "' . pSQL($dateFrom) . '"');
        $sql->orderBy('stat_date ASC');

        $results = Db::getInstance()->executeS($sql);

        $data = [
            'labels' => [],
            'impressions' => [],
            'conversions' => [],
        ];

        for ($i = $days; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('d M', strtotime($date));
            $data['impressions'][$date] = 0;
            $data['conversions'][$date] = 0;
        }

        foreach ($results as $row) {
            $date = $row['stat_date'];
            if ($row['stat_type'] === 'impression') {
                $data['impressions'][$date] = (int) $row['count'];
            } else {
                $data['conversions'][$date] = (int) $row['count'];
            }
        }

        $data['impressions'] = array_values($data['impressions']);
        $data['conversions'] = array_values($data['conversions']);

        return $data;
    }

    public function initContent()
    {
        // Check if stats view requested
        if (Tools::getValue('action') === 'stats') {
            $this->content = $this->renderStats();
            parent::initContent();
            return;
        }

        parent::initContent();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS(_PS_MODULE_DIR_ . 'ps_advanced_popup/views/js/back.js');
        $this->addCSS(_PS_MODULE_DIR_ . 'ps_advanced_popup/views/css/back.css');
    }

    /**
     * Add stats link to row actions
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('stats');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    public function displayStatsLink($token, $id)
    {
        $link = $this->context->link->getAdminLink('AdminSmartPopup') . '&action=stats&id_popup=' . $id;
        return '<a href="' . $link . '" title="' . $this->l('View Stats') . '"><i class="icon-bar-chart"></i></a>';
    }
}
