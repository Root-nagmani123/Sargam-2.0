@extends('admin.layouts.master')
@section('setup_content')
    <div class="card p-3">
        <h4 class="mb-4">Peer Evaluation Form</h4>

        {{-- Add Group --}}
        <div class="mb-3">
            <h5>Add Group</h5>
            <div class="input-group mb-3">
                <input type="text" id="group_name" class="form-control" placeholder="Enter Group Name (e.g. Syndicate-20)">
                <button class="btn btn-success" id="addGroupBtn">Add</button>
            </div>
        </div>

        {{-- Add Column --}}
        <div class="mb-3">
            <h5>Add Evaluation Column</h5>
            <div class="input-group mb-3">
                <input type="text" id="column_name" class="form-control"
                    placeholder="Enter Column Name (e.g. Team Player)">
                <button class="btn btn-primary" id="addColumnBtn">Add</button>
            </div>
        </div>

        {{-- Evaluation Table --}}
        <form method="POST" action="{{ route('peer.store') }}">
            @csrf
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Sr.No</th>
                        <th>Name</th>
                        {{-- <th>Group</th> --}}
                        @foreach ($columns as $column)
                            <th class="column-header" data-id="{{ $column->id }}">
                                {{ $column->column_name }}
                                <input type="checkbox" class="toggle-column" data-id="{{ $column->id }}"
                                    {{ $column->is_visible ? 'checked' : '' }}>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- @foreach ($members as $index => $member)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $member->first_name }}</td>
                        <td>{{ $member->group_name }}</td>
                        @foreach ($columns as $column)
                            <td class="column-cell column-{{ $column->id }}" @if (!$column->is_visible) style="display:none;" @endif>
                                <input type="number" min="1" max="10" name="scores[{{ $member->id }}][{{ $column->id }}]" class="form-control text-center" value="0">
                            </td>
                        @endforeach
                    </tr>
                @endforeach --}}

                  @foreach ($members as $index => $member)
    <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $member->first_name }}</td>
        @foreach ($columns as $column)
            <td class="column-cell column-{{ $column->id }}"
                @if (!$column->is_visible) style="display:none;" @endif>
                <input type="number" min="1" max="10"
                    name="scores[{{ $member->pk }}][{{ $column->id }}]"
                    class="form-control text-center" value="0">
            </td>
        @endforeach
    </tr>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button class="btn btn-primary mt-3">Submit</button>
        </form>
    </div>

    {{-- jQuery for AJAX --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function() {
            // Add Group
            $('#addGroupBtn').click(function() {
                $.post('{{ route('peer.group.store') }}', {
                    _token: '{{ csrf_token() }}',
                    group_name: $('#group_name').val()
                }, function() {
                    alert('Group added!');
                    location.reload();
                });
            });

            // Add Column
            $('#addColumnBtn').click(function() {
                $.post('{{ route('peer.column.store') }}', {
                    _token: '{{ csrf_token() }}',
                    column_name: $('#column_name').val()
                }, function() {
                    alert('Column added!');
                    location.reload();
                });
            });

            // Toggle Column
            $('.toggle-column').change(function() {
                const id = $(this).data('id');
                const checked = $(this).is(':checked');
                $.post('/admin/peer/toggle/' + id, {
                    _token: '{{ csrf_token() }}'
                }, function(res) {
                    if (res.new_state) {
                        $('.column-' + id).show();
                    } else {
                        $('.column-' + id).hide();
                    }
                });
            });
        });
    </script>
@endsection
