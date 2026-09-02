@props(['field', 'name', 'value' => null])
@php
    $f = $field;
    $kind = $f['kind'];
    $label = $f['label'] ?? $f['name'];
    $req = ! empty($f['required']);
    $hint = collect([
        isset($f['max']) ? "máx {$f['max']}" : null,
        isset($f['min']) && $kind !== 'list' ? "mín {$f['min']}" : null,
        $kind === 'list' && (isset($f['min']) || isset($f['max'])) ? trim(($f['min'] ?? '').'–'.($f['max'] ?? '').' ítems') : null,
        $kind === 'richtext' ? 'HTML restringido, se sanitiza' : null,
    ])->filter()->implode(' · ');
    $iconKeys = array_keys(\App\Rendering\IconRegistry::paths());
@endphp

<div class="cms-field" data-kind="{{ $kind }}">
    @if(! in_array($kind, ['boolean']))
        <label class="cms-field__label">{{ $label }}@if($req) <span class="cms-req">*</span>@endif
            @if($hint)<span class="cms-hint">{{ $hint }}</span>@endif
        </label>
    @endif

    @switch($kind)
        @case('textarea')
        @case('richtext')
            <textarea name="{{ $name }}" rows="{{ $kind === 'richtext' ? 6 : 3 }}" @if(isset($f['max'])) maxlength="{{ $f['max'] }}" @endif>{{ $value }}</textarea>
            @break

        @case('number')
            <input type="number" name="{{ $name }}" value="{{ $value }}" step="any"
                   @if(isset($f['min'])) min="{{ $f['min'] }}" @endif @if(isset($f['max'])) max="{{ $f['max'] }}" @endif>
            @break

        @case('boolean')
            <label class="cms-check">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" name="{{ $name }}" value="1" @checked($value)>
                {{ $label }}
            </label>
            @break

        @case('enum')
            <select name="{{ $name }}">
                @if(! $req)<option value="">— sin definir —</option>@endif
                @foreach($f['enum'] as $opt)
                    <option value="{{ $opt }}" @selected((string) $value === (string) $opt || ($value === null && ($f['default'] ?? null) === $opt))>{{ $opt }}</option>
                @endforeach
            </select>
            @break

        @case('color')
            <span class="cms-color">
                <input type="color" value="{{ $value ?: '#000000' }}" oninput="this.nextElementSibling.value=this.value">
                <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="#RRGGBB" pattern="#[0-9a-fA-F]{6}">
            </span>
            @break

        @case('icon')
            <select name="{{ $name }}">
                <option value="">— sin ícono —</option>
                @foreach($iconKeys as $key)
                    <option value="{{ $key }}" @selected($value === $key)>{{ $key }}</option>
                @endforeach
            </select>
            @break

        @case('image')
            <div class="cms-nest">
                <input type="text" name="{{ $name }}[src]" value="{{ data_get($value, 'src') }}" placeholder="URL de la imagen">
                <input type="text" name="{{ $name }}[alt]" value="{{ data_get($value, 'alt') }}" placeholder="Texto alternativo (obligatorio, descriptivo)">
                <select name="{{ $name }}[focal]">
                    @foreach(['center', 'top', 'bottom'] as $fo)
                        <option value="{{ $fo }}" @selected(data_get($value, 'focal', 'center') === $fo)>{{ $fo }}</option>
                    @endforeach
                </select>
            </div>
            @break

        @case('button')
        @case('item')
        @case('object')
            <fieldset class="cms-nest">
                @foreach($f['fields'] as $sub)
                    <x-cms.field :field="$sub" :name="$name.'['.$sub['name'].']'" :value="data_get($value, $sub['name'])" />
                @endforeach
            </fieldset>
            @break

        @case('list')
            @if(($f['item_kind'] ?? null) === 'enum')
                <div class="cms-checks">
                    @foreach($f['item_enum'] as $opt)
                        <label class="cms-check"><input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}"
                            @checked(is_array($value) && in_array($opt, $value, true))> {{ $opt }}</label>
                    @endforeach
                </div>
            @else
                <div class="cms-list" data-list>
                    @foreach((array) ($value ?: []) as $i => $row)
                        <fieldset class="cms-list__row">
                            <label class="cms-check cms-list__del"><input type="checkbox" name="{{ $name }}[{{ $i }}][__remove]" value="1"> borrar</label>
                            @foreach($f['item_fields'] ?? [] as $sub)
                                <x-cms.field :field="$sub" :name="$name.'['.$i.']['.$sub['name'].']'" :value="data_get($row, $sub['name'])" />
                            @endforeach
                        </fieldset>
                    @endforeach
                    <template data-list-template>
                        <fieldset class="cms-list__row">
                            <label class="cms-check cms-list__del"><input type="checkbox" name="{{ $name }}[__IDX__][__remove]" value="1"> borrar</label>
                            @foreach($f['item_fields'] ?? [] as $sub)
                                <x-cms.field :field="$sub" :name="$name.'[__IDX__]['.$sub['name'].']'" :value="null" />
                            @endforeach
                        </fieldset>
                    </template>
                </div>
                <button type="button" class="cms-btn cms-btn--ghost" data-list-add>+ agregar</button>
            @endif
            @break

        @default
            <input type="text" name="{{ $name }}" value="{{ $value }}" @if(isset($f['max'])) maxlength="{{ $f['max'] }}" @endif>
    @endswitch
</div>
