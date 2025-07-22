<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_基本的なモデル作成ができる()
    {
        $user = User::create([
            'name' => $this->faker()->name(),
            'email' => $this->faker()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_fillable属性が正しく設定されている()
    {
        $user = new User;
        $expected = ['name', 'email', 'password'];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_hidden属性が正しく設定されている()
    {
        $user = new User;
        $expected = ['password', 'remember_token'];

        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_パスワードがハッシュ化される()
    {
        $password = 'test-password';
        $user = User::create([
            'name' => $this->faker()->name(),
            'email' => $this->faker()->unique()->safeEmail(),
            'password' => $password,
        ]);

        $this->assertNotEquals($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_email_verified_atが日時型にキャストされる()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    public function test_ファクトリーが正常に動作する()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
    }

    public function test_必須項目が空の場合に例外が発生する()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([]);
    }

    public function test_重複するemailの場合に例外が発生する()
    {
        $email = $this->faker()->unique()->safeEmail();

        User::create([
            'name' => $this->faker()->name(),
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => $this->faker()->name(),
            'email' => $email,
            'password' => Hash::make('password456'),
        ]);
    }

}
