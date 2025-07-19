<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:companies,domain',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url|max:500',
            'website_url' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'url_patterns' => 'nullable|array',
            'url_patterns.*' => 'string',
            'domain_patterns' => 'nullable|array',
            'domain_patterns.*' => 'string',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'zenn_organizations' => 'nullable|array',
            'zenn_organizations.*' => 'string',
            'qiita_username' => 'nullable|string|max:255',
            'zenn_username' => 'nullable|string|max:255',
        ];
    }

    /**
     * エラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '企業名は必須です。',
            'name.max' => '企業名は255文字以内で入力してください。',
            'domain.required' => 'ドメインは必須です。',
            'domain.unique' => 'このドメインは既に登録されています。',
            'logo_url.url' => 'ロゴURLの形式が正しくありません。',
            'website_url.url' => 'ウェブサイトURLの形式が正しくありません。',
        ];
    }
}
