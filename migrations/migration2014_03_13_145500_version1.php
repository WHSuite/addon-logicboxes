<?php
namespace Addon\Logicboxes\Migrations;

use \App\Libraries\BaseMigration;

class Migration2014_03_13_145500_version1 extends BaseMigration
{
    public function up($addon_id)
    {
        // Create the settings category
        $category = new \SettingCategory();
        $category->slug = 'logicboxes';
        $category->title = 'logicboxes_settings';
        $category->is_visible = '1';
        $category->sort = '99';
        $category->addon_id = $addon_id;
        $category->save();

        // Add the settings
        \Setting::insert(
            array(
                array(
                    'slug' => 'logicboxes_api_key',
                    'title' => 'logicboxes_api_key',
                    'field_type' => 'text',
                    'rules' => '',
                    'setting_category_id' => $category->id,
                    'editable' => '1',
                    'required' => '1',
                    'value' => null,
                    'addon_id' => $addon_id,
                    'sort' => '1',
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
                array(
                    'slug' => 'logicboxes_reseller_id',
                    'title' => 'logicboxes_reseller_id',
                    'field_type' => 'text',
                    'rules' => 'integer',
                    'setting_category_id' => $category->id,
                    'editable' => '1',
                    'required' => '1',
                    'value' => null,
                    'addon_id' => $addon_id,
                    'sort' => '2',
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
                array(
                    'slug' => 'logicboxes_enable_sandbox',
                    'title' => 'logicboxes_enable_sandbox',
                    'field_type' => 'checkbox',
                    'rules' => 'min:0|max:1',
                    'setting_category_id' => $category->id,
                    'editable' => '1',
                    'required' => '1',
                    'value' => '0',
                    'addon_id' => $addon_id,
                    'sort' => '3',
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
            )
        );

        $registrar = new \Registrar();
        $registrar->name = 'Logicboxes';
        $registrar->slug = 'logicboxes';
        $registrar->addon_id = $addon_id;
        $registrar->status = '1';
        $registrar->save();
    }

    public function down($addon_id)
    {
        // Remove all settings
        \Setting::where('addon_id', '=', $addon_id)->delete();

        // Remove all settings groups
        \SettingCategory::where('addon_id', '=', $addon_id)->delete();

        // Remove registrar
        \Registrar::where('addon_id', '=', $addon_id)->delete();
    }
}
