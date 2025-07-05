<?php

namespace Tests\Unit;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_created(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => 'Test Description',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://test.com',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Test Company', $company->name);
        $this->assertEquals('test.com', $company->domain);
        $this->assertEquals('Test Description', $company->description);
        $this->assertEquals('https://example.com/logo.png', $company->logo_url);
        $this->assertEquals('https://test.com', $company->website_url);
        $this->assertTrue($company->is_active);
    }

    public function test_company_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Company::create([
            'domain' => 'test.com',
        ]);
    }

    public function test_company_domain_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Company::create([
            'name' => 'Test Company',
        ]);
    }

    public function test_company_domain_must_be_unique(): void
    {
        Company::create([
            'name' => 'Test Company 1',
            'domain' => 'test.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Company::create([
            'name' => 'Test Company 2',
            'domain' => 'test.com',
        ]);
    }

    public function test_company_is_active_defaults_to_true(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
        ]);

        $this->assertTrue($company->is_active);
    }

    public function test_company_can_be_inactive(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'is_active' => false,
        ]);

        $this->assertFalse($company->is_active);
    }

    public function test_company_optional_fields_can_be_null(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'description' => null,
            'logo_url' => null,
            'website_url' => null,
        ]);

        $this->assertNull($company->description);
        $this->assertNull($company->logo_url);
        $this->assertNull($company->website_url);
    }

    public function test_company_fillable_attributes(): void
    {
        $company = new Company();
        $fillable = $company->getFillable();

        $this->assertEquals([
            'name',
            'domain',
            'description',
            'logo_url',
            'website_url',
            'is_active',
        ], $fillable);
    }

    public function test_company_casts_is_active_to_boolean(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
            'is_active' => '1',
        ]);

        $this->assertIsBool($company->is_active);
        $this->assertTrue($company->is_active);
    }

    public function test_company_has_timestamps(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'domain' => 'test.com',
        ]);

        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
    }
}
