<div class="panel">
    <div class="cap">Student class performance graph</div>
    <table class="bars">
    @foreach($rows as $row)
        <tr><td style="width:40%">{{ \Illuminate\Support\Str::limit($row['subject'], 22) }}</td>
        <td><span class="bar me" style="width: {{ max(2, min(100, (float) $row['total'])) }}%"></span><span class="bar avg" style="width: {{ max(2, min(100, (float) $row['average'])) }}%"></span></td></tr>
    @endforeach
    </table>
    <p class="key"><span style="color:#36b8b8">&#9632;</span> Subject score &nbsp; <span style="color:#a98ee0">&#9632;</span> Class average (scale 0–100)</p>
</div>
