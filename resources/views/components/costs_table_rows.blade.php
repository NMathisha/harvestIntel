@forelse($costs as $cost)
    <tr>
        <td>{{ $cost->id }}</td>
        <td>{{ $cost->description }}</td>
        <td>{{ $cost->operation->name ?? '-' }}</td>
        <td>{{ $cost->amount ?? '-' }}</td>
        <td>{{ $cost->incurred_date->format('d-m-Y') ?? '-' }}</td>
        <td>{{ $cost->quantity ?? '-' }}</td>
        <td>{{ $cost->unit ?? '-' }}</td>
        <td>Rs. {{ number_format($cost->unit_price, 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-muted">No costs found.</td>
    </tr>
@endforelse
