<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    /**
     * 認証済みユーザーにアクセスを許可
     * TODO: 認証機能実装時に適切な権限チェックを追加
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 記事更新時のバリデーションルール
     * 部分更新対応のため全フィールドを任意とする
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleId = $this->route('article'); // ルートパラメータからIDを直接取得

        return [
            'platform_id' => ['sometimes', 'integer', 'exists:platforms,id'],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'title' => ['sometimes', 'string', 'max:500'],
            'url' => ['sometimes', 'string', 'max:1000', 'url', "unique:articles,url,{$articleId}"],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:255'],
            'author_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'author' => ['sometimes', 'nullable', 'string', 'max:255'],
            'author_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'bookmark_count' => ['sometimes', 'integer', 'min:0'],
            'likes_count' => ['sometimes', 'integer', 'min:0'],
            'scraped_at' => ['sometimes', 'date'],
        ];
    }

    /**
     * バリデーションエラー時のカスタムメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform_id.exists' => 'プラットフォームが存在しません。',
            'company_id.exists' => '企業が存在しません。',
            'title.max' => 'タイトルは500文字以内で入力してください。',
            'url.max' => 'URLは1000文字以内で入力してください。',
            'url.url' => '有効なURLを入力してください。',
            'url.unique' => 'このURLの記事は既に存在します。',
            'author_url.url' => '有効な投稿者URLを入力してください。',
            'bookmark_count.min' => 'ブックマーク数は0以上を入力してください。',
            'likes_count.min' => 'いいね数は0以上を入力してください。',
        ];
    }
}
