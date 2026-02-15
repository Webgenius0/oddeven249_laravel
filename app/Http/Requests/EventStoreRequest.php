<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;


class EventStoreRequest extends BaseRequest
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
            'title' => 'required|string|max:255',
            'type' => 'required|in:online,offline',
            'entry_fee' => 'nullable|numeric|min:0',
            'location' => 'required|string|max:255',
            'full_location' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:10000', // max 10MB
            'event_restriction' => 'required|in:public,only_invited',
            'is_published' => 'required|boolean',
            'message' => 'nullable|string',
            'sponsors' => 'nullable|array',
            'sponsors.*.user_id' => 'required|exists:users,id',
            'sponsors.*.amount' => 'required|numeric|min:0',
            'sponsors.*.payment_status' => 'nullable|in:pending,completed,failed',
            'collaborators' => 'nullable|array',
            'collaborators.*' => 'required|exists:users,id',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title field must not exceed 255 characters.',
            'type.required' => 'The type field is required.',
            'type.in' => 'The type field must be either online or offline.',
            'entry_fee.numeric' => 'The entry fee must be a number.',
            'entry_fee.min' => 'The entry fee must be at least 0.',
            'location.required' => 'The location field is required.',
            'location.max' => 'The location field must not exceed 255 characters.',
            'full_location.required' => 'The full location field is required.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date field must be a valid date.',
            'photo.image' => 'The photo must be an image file.',
            'photo.max' => 'The photo must not exceed 10MB.',
            'event_restriction.required' => 'The event restriction field is required.',
            'event_restriction.in' => 'The event restriction field must be either public or only_invited.',
            'is_published.required' => 'The is published field is required.',
            'is_published.boolean' => 'The is published field must be true or false.',
            'sponsors.array' => 'The sponsors field must be an array.',
            'sponsors.*.user_id.required' => 'Each sponsor must have a user ID.',
            'sponsors.*.user_id.exists' => 'Each sponsor user ID must exist in the users table.',
            'sponsors.*.amount.required' => 'Each sponsor must have an amount.',
            'sponsors.*.amount.numeric' => 'Each sponsor amount must be a number.',
            'sponsors.*.amount.min' => 'Each sponsor amount must be at least 0.',
            'sponsors.*.payment_status.in' => 'Each sponsor payment status must be either pending, completed, or failed.',
            'collaborators.array' => 'The collaborators field must be an array.',
            'collaborators.*.required' => 'Each collaborator must have a user ID.',
            'collaborators.*.exists' => 'Each collaborator user ID must exist in the users table.',
        ];
    }
}
