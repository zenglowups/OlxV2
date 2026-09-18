<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::select('SELECT pg_advisory_xact_lock(782016)');
            $groups = [
                ['auto', 'Auto', 'Vehicles', 'car', [['autoturisme', 'Autoturisme', 'Cars'], ['motociclete', 'Motociclete', 'Motorcycles'], ['vehicule-utilitare', 'Vehicule utilitare', 'Commercial vehicles']]],
                ['piese-auto', 'Piese auto', 'Car parts', 'parts', [['accesorii-auto', 'Accesorii auto', 'Car accessories'], ['jante-anvelope', 'Jante și anvelope', 'Wheels and tyres']]],
                ['imobiliare', 'Imobiliare', 'Real estate', 'home', [['apartamente', 'Apartamente', 'Apartments'], ['case', 'Case', 'Houses'], ['terenuri', 'Terenuri', 'Land']]],
                ['electronice', 'Electronice', 'Electronics', 'laptop', [['telefoane', 'Telefoane', 'Phones'], ['tablete', 'Tablete', 'Tablets'], ['calculatoare', 'Calculatoare', 'Computers'], ['televizoare', 'Televizoare', 'TVs'], ['audio', 'Audio', 'Audio']]],
                ['gradina', 'Casă și grădină', 'Home and garden', 'garden', [['unelte-gradina', 'Unelte de grădină', 'Garden tools'], ['plante', 'Plante', 'Plants']]],
                ['pescuit', 'Pescuit', 'Fishing', 'fish', [['lansete', 'Lansete', 'Fishing rods'], ['accesorii-pescuit', 'Accesorii de pescuit', 'Fishing accessories']]],
                ['decoratiuni', 'Decorațiuni', 'Decorative objects', 'lamp', [['iluminat', 'Corpuri de iluminat', 'Lighting'], ['obiecte-decorative', 'Obiecte decorative', 'Decorative objects']]],
                ['moda', 'Modă și accesorii', 'Clothing and accessories', 'clothes', [['imbracaminte', 'Îmbrăcăminte', 'Clothes'], ['incaltaminte', 'Încălțăminte', 'Footwear']]],
                ['sport', 'Sport și mișcare', 'Sports', 'bike', [['biciclete', 'Biciclete', 'Bicycles'], ['echipament-sportiv', 'Echipament sportiv', 'Sports equipment']]],
            ];
            foreach ($groups as $position => [$slug,$ro,$en,$icon,$children]) {
                $root = Category::firstOrCreate(['slug' => $slug], ['name_ro' => $ro, 'name_en' => $en, 'icon' => $icon, 'position' => $position * 10]);
                foreach ($children as $index => [$childSlug,$childRo,$childEn]) {
                    Category::firstOrCreate(['slug' => $childSlug], ['parent_id' => $root->id, 'name_ro' => $childRo, 'name_en' => $childEn, 'icon' => $icon, 'position' => $index * 10]);
                }
            }
            $this->attribute('autoturisme', 'brand', 'Marcă', 'Brand', 'text', true);
            $this->attribute('autoturisme', 'model', 'Model', 'Model', 'text', true);
            $this->attribute('autoturisme', 'year', 'An de fabricație', 'Manufacturing year', 'number', true, 1900, (int) date('Y') + 1);
            $this->attribute('autoturisme', 'mileage', 'Kilometraj', 'Mileage', 'number', true, 0, 9999999, 'km');
            $this->attribute('autoturisme', 'fuel', 'Combustibil', 'Fuel', 'select', true, null, null, null, [['petrol', 'Benzină', 'Petrol'], ['diesel', 'Motorină', 'Diesel'], ['electric', 'Electric', 'Electric'], ['hybrid', 'Hibrid', 'Hybrid'], ['other', 'Altele', 'Other']]);
            $this->attribute('autoturisme', 'transmission', 'Cutie de viteze', 'Transmission', 'select', true, null, null, null, [['manual', 'Manuală', 'Manual'], ['automatic', 'Automată', 'Automatic']]);
            $this->attribute('imobiliare', 'area', 'Suprafață', 'Area', 'number', true, 1, 10000000, 'm²');
            $this->attribute('apartamente', 'rooms', 'Camere', 'Rooms', 'number', true, 1, 100);
            $this->attribute('electronice', 'brand', 'Marcă', 'Brand', 'text', false);
            $this->attribute('electronice', 'condition', 'Stare', 'Condition', 'select', true, null, null, null, [['new', 'Nou', 'New'], ['like_new', 'Ca nou', 'Like new'], ['used', 'Folosit', 'Used']]);
            $this->attribute('electronice', 'warranty', 'Garanție disponibilă', 'Warranty available', 'boolean', false);
            $this->attribute('calculatoare', 'storage', 'Stocare', 'Storage', 'number', false, 1, 1000000, 'GB');
        });
    }

    private function attribute(string $slug, string $key, string $ro, string $en, string $type, bool $required, ?float $min = null, ?float $max = null, ?string $unit = null, array $options = []): void
    {
        $category = Category::where('slug', $slug)->sole();
        CategoryAttribute::firstOrCreate(['category_id' => $category->id, 'key' => $key], [
            'label_ro' => $ro, 'label_en' => $en, 'type' => $type, 'is_required' => $required, 'is_filterable' => in_array($type, ['number', 'select', 'boolean']),
            'minimum' => $min, 'maximum' => $max, 'unit' => $unit, 'options' => $options ? array_map(fn ($o) => ['value' => $o[0], 'label_ro' => $o[1], 'label_en' => $o[2]], $options) : null,
        ]);
    }
}
