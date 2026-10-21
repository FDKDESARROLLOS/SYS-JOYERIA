<?php

namespace Tests\Unit;

use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class StoreSettingTest extends TestCase
{
    public function test_store_setting_matches_its_model_configuration(): void
    {
        $storeSetting = new StoreSetting;

        $this->assertContains(HasFactory::class, class_uses_recursive(StoreSetting::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(StoreSetting::class));
        $this->assertSame([
            'store_name',
            'email',
            'phone',
            'whatsapp',
            'address',
            'city',
            'state',
            'postal_code',
            'country',
        ], $storeSetting->getFillable());
        $this->assertSame(['id' => 'int'], $storeSetting->getCasts());
    }
}
