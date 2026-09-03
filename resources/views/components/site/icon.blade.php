@props(['name' => null, 'type' => null, 'class' => 'wp-icon'])
{!! \App\Rendering\IconRegistry::svg($name, $type, $class) !!}
