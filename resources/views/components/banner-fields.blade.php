@props(['quiz' => null])
@php
  $current = $quiz?->banner_url;
  $isUrl   = $quiz?->banner && preg_match('#^https?://#i', $quiz->banner);
@endphp
<div class="form-group banner-fields">
  <label class="form-label" for="banner-file">Banner image (optional)</label>
  @if($current)
    <img class="banner-preview" src="{{ $current }}" alt="Current banner" loading="lazy" referrerpolicy="no-referrer" />
  @endif
  <input type="file" id="banner-file" name="banner_file" class="form-control" accept="image/jpeg,image/png,image/webp" />
  <span class="field-hint">JPG, PNG or WebP, up to 2 MB. Wide images (about 3:1) look best.</span>
  @error('banner_file')<span class="field-error">{{ $message }}</span>@enderror
  <label class="form-label banner-url-label" for="banner-url">Or paste an image URL</label>
  <input type="url" id="banner-url" name="banner_url" class="form-control" value="{{ old('banner_url', $isUrl ? $quiz->banner : '') }}" placeholder="https://..." />
  @error('banner_url')<span class="field-error">{{ $message }}</span>@enderror
  @if($current)
    <label class="form-check banner-remove">
      <input type="checkbox" name="remove_banner" value="1" />
      <span>Remove banner</span>
    </label>
  @endif
</div>
