@props([
    'id',
    'title' => null,
    'icon' => null,
    'maxWidth' => 'md', // sm | md | lg | xl
    'persistent' => false,
])

@php
    $widths = [
        'sm' => 'max-width: 420px;',
        'md' => 'max-width: 540px;',
        'lg' => 'max-width: 720px;',
        'xl' => 'max-width: 900px;',
    ];

    $maxWidthStyle = $widths[$maxWidth] ?? $widths['md'];
    $cleanIcon = $icon ? (str_starts_with($icon, 'bi-') ? 'bi ' . $icon : $icon) : null;
    $titleId = "{$id}-title";
@endphp

<div
    id="{{ $id }}"
    class="ds-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $titleId }}"
    tabindex="-1"
    style="display: none; position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center; padding: 20px; overflow-y: auto;"
    @if(!$persistent)
        onkeydown="if(event.key==='Escape') closeDsModal('{{ $id }}')"
    @endif
>
    {{-- Modal Backdrop --}}
    <div
        class="ds-modal-backdrop"
        style="position: fixed; inset: 0; background: rgba(10, 5, 5, 0.75); backdrop-filter: blur(8px); transition: opacity 0.25s ease;"
        @if(!$persistent)
            onclick="closeDsModal('{{ $id }}')"
        @endif
        aria-hidden="true"
    ></div>

    {{-- Modal Dialog Panel --}}
    <div
        class="ds-modal-panel"
        style="position: relative; width: 100%; {{ $maxWidthStyle }} background: var(--ds-surface, #1E1515); border: 1px solid var(--ds-border, rgba(212, 175, 55, 0.25)); border-radius: var(--ds-radius-xl, 20px); box-shadow: var(--ds-shadow-lg, 0 20px 50px rgba(0,0,0,0.6)); overflow: hidden; z-index: 1051; transform: scale(0.96); transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.25s ease;"
    >
        @if($title || $icon)
            <header style="display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 18px 24px; border-bottom: 1px solid var(--ds-border-subtle, rgba(255,255,255,0.08));">
                <div style="display: flex; align-items: center; gap: 10px;">
                    @if($cleanIcon)
                        <div style="width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--ds-gold-glow, rgba(212, 175, 55, 0.15)); color: var(--ds-gold, #D4AF37); font-size: 1.1rem;">
                            <i class="{{ $cleanIcon }}" aria-hidden="true"></i>
                        </div>
                    @endif
                    <h3 id="{{ $titleId }}" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--ds-text-primary, #FCF8F2); letter-spacing: -0.01em;">
                        {{ $title }}
                    </h3>
                </div>
                <button
                    type="button"
                    onclick="closeDsModal('{{ $id }}')"
                    aria-label="Close dialog"
                    style="background: transparent; border: none; color: var(--ds-text-muted, #A39683); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; transition: background 0.15s ease, color 0.15s ease;"
                    onmouseover="this.style.background='rgba(255,255,255,0.06)'; this.style.color='#fff';"
                    onmouseout="this.style.background='transparent'; this.style.color='var(--ds-text-muted, #A39683)';"
                >
                    <i class="bi bi-x-lg" style="font-size: 0.95em;"></i>
                </button>
            </header>
        @endif

        {{-- Modal Body --}}
        <div style="padding: 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
            {{ $slot }}
        </div>

        {{-- Optional Footer --}}
        @if(isset($footer))
            <footer style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid var(--ds-border-subtle, rgba(255,255,255,0.08)); background: rgba(0,0,0,0.15);">
                {{ $footer }}
            </footer>
        @endif
    </div>
</div>

<script>
if (typeof window.openDsModal === 'undefined') {
    window.openDsModal = function(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
            const panel = modal.querySelector('.ds-modal-panel');
            if (panel) {
                panel.style.transform = 'scale(1)';
                panel.style.opacity = '1';
            }
        });
        modal.focus();
    };

    window.closeDsModal = function(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        const panel = modal.querySelector('.ds-modal-panel');
        if (panel) {
            panel.style.transform = 'scale(0.96)';
            panel.style.opacity = '0';
        }
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 200);
    };
}
</script>
