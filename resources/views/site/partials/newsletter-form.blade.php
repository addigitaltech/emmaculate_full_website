<form method="post" action="{{ route('newsletter.subscribe') }}" class="{{ $class ?? 'footer-form' }}">
    @csrf
    <div class="honeypot" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <label class="sr-only" for="{{ $inputId ?? 'newsletter-email' }}">Email address</label>
    <input id="{{ $inputId ?? 'newsletter-email' }}" type="email" name="newsletter_email" required maxlength="190" autocomplete="email" placeholder="{{ $placeholder ?? 'Enter your email' }}">
    <button type="submit" class="btn {{ $buttonClass ?? 'btn--maroon' }}">{{ $buttonLabel ?? 'Subscribe' }}</button>
</form>
