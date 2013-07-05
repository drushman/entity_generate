<?php

class EntityGenarate {
  
  public $faker;
  
  public $entity_type;
  
  public $bundle;
  
  public $entity;
  
  public $properties;
  
  public $fields;
  
  public $wrapper;


  public function __construct($entity_type, $bundle) {
    libraries_load('faker');
    $this->faker = Faker\Factory::create();
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->info();
  }
  
  public function info() {
    $this->properties = entity_get_property_info($this->entity_type);
    $this->properties = $this->properties['properties'];
    $this->fields = field_info_instances($this->entity_type, $this->bundle);
  }
  
  public function generate() {
    $values = array();
    foreach ($this->properties as $name => $property) {
      if ($property['type'] == 'text') {
        $values[$name] = $this->faker->text(40);
      }
    }
    $this->entity = entity_create($this->entity_type, $values);
    $this->wrapper = entity_metadata_wrapper($this->entity_type, $this->entity);
    foreach ($this->fields as $field_name => $field) {
      if ($field_name == 'group_group') {
        continue;
      }
//      dsm($field_name);
//      dsm($field);
      
      
      if ($value = $this->fieldValues($field)) {
//        dsm($field_name);
        $this->wrapper->{$field_name}->set($value);
      }
    }
    $this->wrapper->save();
  }
  
  public function fieldValues($field) {
    $field = field_info_field($field['field_name']);
    $value = FALSE;
    switch ($field['type']) {
      case 'list_integer':
        $value = $this->generateList($field);
        break;
      case 'list_float':
        $value = $this->generateList($field);
        break;
      case 'list_boolean':
        $value = $this->generateList($field);
        break;
      case 'list_text':
        $value = $this->generateList($field);
        break;
      case 'number_integer':
        $value = $this->faker->number;
        break;
      case 'number_decimal':
        $value = $this->faker->number;
        break;
      case 'number_float':
        $value = $this->faker->number;
        break;
      case 'text':
        $value = $this->faker->text;
        break;
      case 'text_long':
        $value = $this->generateLongText($field);
        break;
      case 'text_with_summary':
        $value = $this->generateLongText($field);
        break;
      case 'taxonomy_term_reference':
        $value = $this->generateTaxonomy($field);
        break;
      case 'file':
        $value = $this->generateFile($field);
        break;
    }
    return $value;
  }
  
  public function generateTaxonomy($field) {
    // TODO: For free tagging vocabularies that do not already have terms, this
    // will not result in any tags being added.
    $machine_name = $field['settings']['allowed_values'][0]['vocabulary'];
    $vocabulary = taxonomy_vocabulary_machine_name_load($machine_name);
    if ($max = db_query('SELECT MAX(tid) FROM {taxonomy_term_data} WHERE vid = :vid', array(':vid' => $vocabulary->vid))->fetchField()) {
      $candidate = mt_rand(1, $max);
      $query = db_select('taxonomy_term_data', 't');
      $tid = $query
                ->fields('t', array('tid'))
                ->condition('t.vid', $vocabulary->vid, '=')
                ->condition('t.tid', $candidate, '>=')
                ->range(0,1)
                ->execute()
                ->fetchField();
      return (int) $tid;
    }
  }


  public function generateLongText($field) {
    $instance = field_info_instance($this->entity_type, $field['field_name'], $this->bundle);
    $defvalue = field_get_default_value($this->entity_type, $this->entity, $field, $instance);
    return !empty($defvalue) ? FALSE : array(
      'value' => $this->faker->paragraph(100),
      'format' => 'full_html'
    );
  }

  
  /**
   * @TODO
   */
  public function generateList($field) {
    $object_field = array();
    if ($allowed_values = list_allowed_values($field)) {
      $keys = array_keys($allowed_values);
      $object_field['value'] = $keys[mt_rand(0, count($allowed_values) - 1)];
    }
    dsm($object_field);
    return $object_field;
  }
  
  
  function generateFile($field) {
    static $file;
    $instance = field_info_instance($this->entity_type, $field['field_name'], $this->bundle);
    if (empty($file)) {
      if ($path = $this->generateTextFile()) {
        $source = new stdClass();
        $source->uri = $path;
        $source->uid = 1; // TODO: randomize? use case specific.
        $source->filemime = 'text/plain';
        $source->filename = array_pop(explode("//", $path));
        $destination_dir = $field['settings']['uri_scheme'] . '://' . $instance['settings']['file_directory'];
        file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = file_move($source, $destination, FILE_CREATE_DIRECTORY);
      }
      else {
        return FALSE;
      }
    }
    return !$file ? FALSE : $file->fid;
  }

  /**
   * Private function for generating a random text file.
   */
  function generateTextFile($filesize = 1024) {
    if ($tmp_file = drupal_tempnam('temporary://', 'filefield_')) {
      $destination = $tmp_file . '.txt';
      file_unmanaged_move($tmp_file, $destination);

      $fp = fopen($destination, 'w');
      fwrite($fp, str_repeat('01', $filesize/2));
      fclose($fp);

      return $destination;
    }
  }
  
}
