<?php
require_once __DIR__ . '/entity_genarate.php';

function entity_generate_entities_info() {
  $data = array();
  $entities = entity_get_info();
  foreach ($entities as $entity_type => $entity) {
    $properties = entity_get_property_info($entity_type);
    $data[$entity_type] = $properties;
    foreach ($entities[$entity_type]['bundles'] as $bundle_name => $settings) {
      $fields = field_info_instances($entity_type, $bundle_name);
      $data[$entity_type][$bundle_name]['fields'] = $fields;
    }
  }
  return $data;
}

function entity_generate_form() {
//  dsm(entity_generate_entities_info());
  
  libraries_load('faker');

  $faker = Faker\Factory::create();
//  dsm($faker->name);
//  dsm($faker->address);
//  dsm($faker->text);
//  dsm($faker->phoneNumber);
//  dsm($faker->text(40));
//  dsm($faker->email);
//  dsm($faker->paragraph(100));
  
  $generate = new EntityGenarate('course', 'course');
  $generate->generate();
//  dsm($generate->properties);
//  dsm($generate->fields);
  
  return "Entity";
}
