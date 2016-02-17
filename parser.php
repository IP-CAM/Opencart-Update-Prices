<?php

/*
    Module Controller

    @author PavoPhilip
    Copyright (c) 2016 Philip Pavo
    All rights reserved

    github.com/PhilipPavo/Opencart-Update-Prices/

*/
class ControllerModuleParser extends Controller
{
    private $error = array();
    public function index()
    {
        $this->language->load('module/parser');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('parser', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            if (isset($this->request->post['prices'])) {
            } else {
                $this->redirect($this->url->link('module/parser', 'token=' . $this->session->data['token'], 'SSL'));
            }
        }
        $this->data['heading_title']       = $this->language->get('heading_title');
        $this->data['text_enabled']        = $this->language->get('text_enabled');
        $this->data['text_disabled']       = $this->language->get('text_disabled');
        $this->data['text_content_top']    = $this->language->get('text_content_top');
        $this->data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $this->data['text_column_left']    = $this->language->get('text_column_left');
        $this->data['text_column_right']   = $this->language->get('text_column_right');
        $this->data['entry_code']          = $this->language->get('entry_code');
        $this->data['entry_layout']        = $this->language->get('entry_layout');
        $this->data['entry_position']      = $this->language->get('entry_position');
        $this->data['entry_status']        = $this->language->get('entry_status');
        $this->data['entry_sort_order']    = $this->language->get('entry_sort_order');
        $this->data['button_save']         = $this->language->get('button_save');
        $this->data['button_cancel']       = $this->language->get('button_cancel');
        $this->data['button_add_module']   = $this->language->get('button_add_module');
        $this->data['button_remove']       = $this->language->get('button_remove');
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }
        if (isset($this->error['code'])) {
            $this->data['error_code'] = $this->error['code'];
        } else {
            $this->data['error_code'] = '';
        }
        $this->data['breadcrumbs']   = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('module/parser', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['action']        = $this->url->link('module/parser', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel']        = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['source_url']    = $this->config->get('source_url');
        $this->data['from_usd']      = $this->config->get('from_usd');
        $this->data['act']           = 'index';
        $this->data['price_up']      = $this->config->get('prices') ? $this->config->get('prices') : array();
        $this->data['link_update']   = $this->url->link('module/parser', 'act=update&token=' . $this->session->data['token'], 'SSL');
        $this->data['products']      = array();
        if (isset($this->request->get['act'])) {
            $this->data['act'] = $this->request->get['act'];
        }
        function isAssoc($arr)
        {
            return array_keys($arr) !== range(0, count($arr) - 1);
        }
        function to_items($data)
        {
            $out = array();
            if (isAssoc($data)) {
                if (isset($data['Category'])) {
                    $out = array_merge($out, to_items($data['Category']));
                } else if (isset($data['Nomenclature'])) {
                    $out = array_merge($out, to_items($data['Nomenclature']));
                } else if (isset($data['Item'])) {
                    $out = array_merge($out, to_items($data['Item']));
                }
            } else {
                for ($i = 0; $i < count($data); $i++) {
                    if (isset($data[$i]['article'])) {
                        $out[] = $data[$i];
                    } else {
                        $out = array_merge($out, to_items($data[$i]));
                    }
                }
            }
            return $out;
        }
        if (isset($this->request->post['prices'])) {
            $products = $this->getProducts();
            for ($i = 0; $i < count($this->request->post['prices']); $i++) {
                $new = $this->request->post['prices'][$i];
                foreach ($products as $product) {
                    if ($new['sku'] == $product['sku']) {
                        if (($product['price'] - ($new['up_percent'] / 100 * $product['price']) - $new['up_rub']) != $new['price']) {
                            $this->db->query("UPDATE  " . DB_PREFIX . "product set price =" . ($new['price'] + ($new['price'] * $new['up_percent'] / 100) + $new['up_rub']) . " WHERE sku=" . $product['sku']);
                        }
                    }
                }
            }
        } else if ($this->data['act'] == 'update') {
            $from_usd = $this->data['from_usd'];
            $products = $this->getProducts();
            $xml      = new simpleXml2Array(file_get_contents($this->data['source_url']), null);
            $data     = to_items($xml->arr['Categories']);
            foreach ($data as $value) {
                if (isset($value['code']) && (isset($value['priceUSD']) || isset($value['priceRUR']))) {
                    $priceUSD = -1;
                    $priceRUR = -1;
                    if (isset($value['priceUSD'][0]) && $value['priceUSD'][0] != 0) {
                        $priceUSD = doubleval($value['priceUSD'][0]);
                    }
                    if (isset($value['priceRUR'][0]) && $value['priceRUR'][0] != 0) {
                        $priceRUR = doubleval($value['priceRUR'][0]);
                    }
                    $code = trim($value['code']['0']);
                    foreach ($products as $product) {
                        if ($code == $product['sku']) {
                            if (isset($product['price'])) {
                                $price = doubleval($product['price']);
                                if ($priceRUR > 0) {
                                    $price = $priceRUR;
                                } else if ($priceUSD > 0) {
                                    $price = $from_usd * $priceUSD;
                                }
                                if ($product['price'] != $price) {
                                    $this->data['products'][] = array(
                                        'name' => $product['name'],
                                        'sku' => $product['sku'],
                                        'price' => $product['price'],
                                        'new_price' => $price,
                                        'up_percent' => 0,
                                        'up_rub' => 0
                                    );
                                } else {
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->load->model('design/layout');
        $this->data['layouts'] = $this->model_design_layout->getLayouts();
        $this->template        = 'module/parser.tpl';
        $this->children        = array(
            'common/header',
            'common/footer'
        );
        $this->response->setOutput($this->render());
    }
    public function getProducts($data = array())
    {
        $sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";
        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
            } else {
                $sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
            }
            if (!empty($data['filter_filter'])) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
            } else {
                $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
            }
        } else {
            $sql .= " FROM " . DB_PREFIX . "product p";
        }
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int) $this->config->get('config_store_id') . "'";
        if (!empty($data['filter_category_id'])) {
            if (!empty($data['filter_sub_category'])) {
                $sql .= " AND cp.path_id = '" . (int) $data['filter_category_id'] . "'";
            } else {
                $sql .= " AND p2c.category_id = '" . (int) $data['filter_category_id'] . "'";
            }
            if (!empty($data['filter_filter'])) {
                $implode = array();
                $filters = explode(',', $data['filter_filter']);
                foreach ($filters as $filter_id) {
                    $implode[] = (int) $filter_id;
                }
                $sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
            }
        }
        if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
            $sql .= " AND (";
            if (!empty($data['filter_name'])) {
                $implode = array();
                $words   = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));
                foreach ($words as $word) {
                    $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
                }
                if ($implode) {
                    $sql .= " " . implode(" AND ", $implode) . "";
                }
                if (!empty($data['filter_description'])) {
                    $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
                }
            }
            if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
                $sql .= " OR ";
            }
            if (!empty($data['filter_tag'])) {
                $sql .= "pd.tag LIKE '%" . $this->db->escape($data['filter_tag']) . "%'";
            }
            if (!empty($data['filter_name'])) {
                $sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
                $sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
            }
            $sql .= ")";
        }
        if (!empty($data['filter_manufacturer_id'])) {
            $sql .= " AND p.manufacturer_id = '" . (int) $data['filter_manufacturer_id'] . "'";
        }
        $sql .= " GROUP BY p.product_id";
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added'
        );
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
            } elseif ($data['sort'] == 'p.price') {
                $sql .= " ORDER BY (CASE WHEN special IS NOT NULL THEN special WHEN discount IS NOT NULL THEN discount ELSE p.price END)";
            } else {
                $sql .= " ORDER BY " . $data['sort'];
            }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC, LCASE(pd.name) DESC";
        } else {
            $sql .= " ASC, LCASE(pd.name) ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }
        $product_data = array();
        $query        = $this->db->query($sql);
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
        }
        return $product_data;
    }
    public function getProduct($product_id)
    {
        $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND customer_group_id = '" . (int) $this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int) $this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int) $this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int) $this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int) $product_id . "' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int) $this->config->get('config_store_id') . "'");
        if ($query->num_rows) {
            return array(
                'product_id' => $query->row['product_id'],
                'name' => $query->row['name'],
                'description' => $query->row['description'],
                'meta_description' => $query->row['meta_description'],
                'meta_keyword' => $query->row['meta_keyword'],
                'tag' => $query->row['tag'],
                'model' => $query->row['model'],
                'sku' => $query->row['sku'],
                'upc' => $query->row['upc'],
                'ean' => $query->row['ean'],
                'jan' => $query->row['jan'],
                'isbn' => $query->row['isbn'],
                'mpn' => $query->row['mpn'],
                'location' => $query->row['location'],
                'quantity' => $query->row['quantity'],
                'stock_status' => $query->row['stock_status'],
                'image' => $query->row['image'],
                'manufacturer_id' => $query->row['manufacturer_id'],
                'manufacturer' => $query->row['manufacturer'],
                'price' => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                'special' => $query->row['special'],
                'reward' => $query->row['reward'],
                'points' => $query->row['points'],
                'tax_class_id' => $query->row['tax_class_id'],
                'date_available' => $query->row['date_available'],
                'weight' => $query->row['weight'],
                'weight_class_id' => $query->row['weight_class_id'],
                'length' => $query->row['length'],
                'width' => $query->row['width'],
                'height' => $query->row['height'],
                'length_class_id' => $query->row['length_class_id'],
                'subtract' => $query->row['subtract'],
                'rating' => round($query->row['rating']),
                'reviews' => $query->row['reviews'] ? $query->row['reviews'] : 0,
                'minimum' => $query->row['minimum'],
                'sort_order' => $query->row['sort_order'],
                'status' => $query->row['status'],
                'date_added' => $query->row['date_added'],
                'date_modified' => $query->row['date_modified'],
                'viewed' => $query->row['viewed']
            );
        } else {
            return false;
        }
    }
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/parser')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['source_url']) {
            $this->error['code'] = $this->language->get('error_code');
        }
        if (!$this->request->post['from_usd']) {
            $this->error['code'] = $this->language->get('error_code');
        }
        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
class simpleXml2Array
{
    public $namespaces, $arr;
    public function __construct($xmlstring, $namespaces = null)
    {
        $xml              = new simpleXmlIterator($xmlstring, null);
        $this->namespaces = is_null($namespaces) ? null : $xml->getNamespaces(true);
        $this->arr        = $this->xmlToArray($xml, $namespaces);
    }
    public function xmlToArray($xml, $namespaces = null)
    {
        $a = array();
        $xml->rewind();
        while ($xml->valid()) {
            $key = $xml->key();
            if (!isset($a[$key])) {
                $a[$key] = array();
                $i       = 0;
            } else {
                $i = count($a[$key]);
            }
            $simple = true;
            foreach ($xml->current()->attributes() as $k => $v) {
                $a[$key][$i][$k] = (string) $v;
                $simple          = false;
            }
            if ($this->namespaces) {
                foreach ($this->namespaces as $nid => $name) {
                    foreach ($xml->current()->attributes($name) as $k => $v) {
                        $a[$key][$i][$nid . ':' . $k] = ( string ) $v;
                        $simple                       = false;
                    }
                }
            }
            if ($xml->hasChildren()) {
                if ($simple)
                    $a[$key][$i] = $this->xmlToArray($xml->current(), $this->namespaces);
                else
                    $a[$key][$i]['content'] = $this->xmlToArray($xml->current(), $this->namespaces);
            } else {
                if ($simple) {
                    $a[$key][$i] = strval($xml->current());
                } else {
                    $a[$key][$i]['content'] = strval($xml->current());
                }
            }
            $i++;
            $xml->next();
        }
        return $a;
    }
}
?>
