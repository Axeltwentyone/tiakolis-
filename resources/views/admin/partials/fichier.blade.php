{{-- Bouton « choisir un fichier » : affiche le nom choisi et l'aperçu dans #{{ $cible }} --}}
<label class="btn btn-ligne w-full cursor-pointer justify-start! overflow-hidden normal-case! tracking-normal!">
    <svg viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>
    <span data-nom data-defaut="{{ $label }}" class="truncate">{{ $label }}</span>
    <input type="file" name="{{ $name }}{{ ! empty($multiple) ? '[]' : '' }}" accept="{{ $accept }}" class="sr-only" @if (! empty($cible)) data-fichier="{{ $cible }}" @endif @if (! empty($multiple)) multiple @endif @if (! empty($requis)) required @endif>
</label>
