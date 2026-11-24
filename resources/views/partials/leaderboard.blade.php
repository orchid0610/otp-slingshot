@forelse($board as $i => $row)
    <li>
        <span class="pos">{{ $i + 1 }}</span>
        <span class="who">{{ $row->email }}</span>
        <span class="score"><b>{{ $row->mistakes }}</b> {{ $row->mistakes === 1 ? 'mistake' : 'mistakes' }}<br>{{ number_format($row->duration_ms / 1000, 1) }}s</span>
    </li>
@empty
    <li class="muted">No one has finished yet. Be the first!</li>
@endforelse
