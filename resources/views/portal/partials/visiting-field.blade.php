{{-- Reception visitor log: who they're here to see, asked every visit (new and returning visitors). --}}
<label class="sp-field">
    <span>Who are you here to see?</span>
    <input type="text" name="visiting" value="{{ $old['visiting'] ?? '' }}" maxlength="100" autocomplete="off" placeholder="Name or department" required @if($fieldError('visiting')) aria-invalid="true" @endif>
    @if($fieldError('visiting'))<small class="sp-field-error">{{ $fieldError('visiting') }}</small>@endif
</label>
