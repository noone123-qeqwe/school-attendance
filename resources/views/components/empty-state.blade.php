@props(['icon' => 'bi bi-inbox', 'title' => 'Nothing here yet', 'message' => '', 'action' => null])

<div class="ent-empty-state {{ $attributes->get('class') }}" style="text-align:center; padding:64px 24px; display:flex; flex-direction:column; align-items:center; justify-content:center; width:100%; min-height:300px; background:rgba(255,255,255,0.02); border:1px dashed rgba(255,255,255,0.06); border-radius:16px;">
    <div style="font-size:3.5rem; color:rgba(255,255,255,0.15); margin-bottom:20px; display:flex; align-items:center; justify-content:center; width:90px; height:90px; background:rgba(255,255,255,0.03); border-radius:24px; margin-left:auto; margin-right:auto; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <i class="{{ $icon }}"></i>
    </div>
    <h4 style="font-size:1.25rem; font-weight:700; color:#f3e7cd; margin:0 0 8px 0; letter-spacing:-0.01em;">
        {{ $title }}
    </h4>
    @if($message)
        <p style="font-size:0.875rem; color:#b39b82; max-width:400px; margin:0 auto 24px auto; line-height:1.6;">
            {{ $message }}
        </p>
    @endif
    @if($action)
        <div>
            {{ $action }}
        </div>
    @endif
</div>
