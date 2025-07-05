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
        $faker = $this->faker();
        $companyName = $faker->company;
        $domain = $faker->domainName;
        $description = $faker->text(100);
        $logoUrl = $faker->url;
        $websiteUrl = $faker->url;

        $company = Company::create([
            'name' => $companyName,
            'domain' => $domain,
            'description' => $description,
            'logo_url' => $logoUrl,
            'website_url' => $websiteUrl,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals($companyName, $company->name);
        $this->assertEquals($domain, $company->domain);
        $this->assertEquals($description, $company->description);
        $this->assertEquals($logoUrl, $company->logo_url);
        $this->assertEquals($websiteUrl, $company->website_url);
        $this->assertTrue($company->is_active);
    }

    public function test_company_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([
            'domain' => $this->faker()->domainName,
        ]);
    }

    public function test_company_domain_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([
            'name' => $this->faker()->company,
        ]);
    }

    public function test_company_domain_must_be_unique(): void
    {
        $faker = $this->faker();
        $domain = $faker->domainName;

        Company::create([
            'name' => $faker->company,
            'domain' => $domain,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([
            'name' => $faker->company,
            'domain' => $domain,
        ]);
    }

    public function test_company_is_active_defaults_to_true(): void
    {
        $faker = $this->faker();
        $company = Company::create([
            'name' => $faker->company,
            'domain' => $faker->domainName,
        ]);

        $this->assertTrue($company->is_active);
    }

    public function test_company_can_be_inactive(): void
    {
        $faker = $this->faker();
        $company = Company::create([
            'name' => $faker->company,
            'domain' => $faker->domainName,
            'is_active' => false,
        ]);

        $this->assertFalse($company->is_active);
    }

    public function test_company_optional_fields_can_be_null(): void
    {
        $faker = $this->faker();
        $company = Company::create([
            'name' => $faker->company,
            'domain' => $faker->domainName,
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
        $company = new Company;
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
        $faker = $this->faker();
        $company = Company::create([
            'name' => $faker->company,
            'domain' => $faker->domainName,
            'is_active' => '1',
        ]);

        $this->assertIsBool($company->is_active);
        $this->assertTrue($company->is_active);
    }

    public function test_company_has_timestamps(): void
    {
        $faker = $this->faker();
        $company = Company::create([
            'name' => $faker->company,
            'domain' => $faker->domainName,
        ]);

        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
    }
}
