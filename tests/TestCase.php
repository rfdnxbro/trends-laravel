<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 日本語化したFakerインスタンスを取得
     */
    protected function faker(): \Faker\Generator
    {
        return \Faker\Factory::create('ja_JP');
    }
}
