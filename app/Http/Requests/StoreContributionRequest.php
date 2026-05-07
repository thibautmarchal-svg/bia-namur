<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;     // form public, pas d'auth requise
    }

    public function rules(): array
    {
        return [
            // Honeypot anti-bot : champ piège vide attendu
            'website_url' => ['nullable', 'max:0'],

            'name' => ['required', 'string', 'min:3', 'max:120'],
            'type' => ['required', 'string', 'in:cafe,restaurant,bar,boulangerie,librairie,patrimoine,parc,marche,culture,hidden_gem'],
            'description' => ['required', 'string', 'min:30', 'max:500'],
            'address' => ['nullable', 'string', 'max:200'],
            'neighborhood' => ['nullable', 'string', 'max:80'],
            'why' => ['nullable', 'string', 'max:300'],

            // Coordonnees du suggesteur — optionnelles, pour suivi de la decision
            'contributor_email' => ['nullable', 'email:rfc', 'max:255'],
            'contributor_name' => ['nullable', 'string', 'max:80'],

            // Photo optionnelle : 5 MB max, mime image strict (validation
            // par magic bytes dans PhotoUploadService, pas par header HTTP)
            'photo' => ['nullable', 'file', 'image', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du lieu est obligatoire.',
            'name.min' => 'Le nom doit faire au moins 3 caractères.',
            'type.required' => 'Choisis un type de lieu.',
            'type.in' => 'Ce type n\'est pas reconnu.',
            'description.required' => 'Une description est obligatoire (au moins 30 caractères).',
            'description.min' => 'La description doit faire au moins 30 caractères.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'photo.image' => 'Le fichier doit être une image (JPG, PNG ou WebP).',
            'photo.max' => 'La photo ne peut pas dépasser 5 Mo.',
            'photo.mimetypes' => 'Format photo non supporté. Utilise JPG, PNG ou WebP.',
        ];
    }
}
