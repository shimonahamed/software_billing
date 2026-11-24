
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billing Configuration</title>
</head>
<body>
<h2>Billing API Configuration</h2>

@if (session('success'))
    <p>{{ session('success') }}</p>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST"
      action="{{ route('software-billing.configuration.store') }}">
    @csrf

    <div>
        <label>API URL</label>
        <input type="url" name="api_url"
               value="{{ old('api_url', $configuration->api_url ?? '') }}"
               required>
    </div>

    <div>
        <label>API Token</label>
        <input type="password" name="api_token"
               autocomplete="new-password"
               placeholder="Leave blank to keep existing token">
    </div>

    <div>
        <label>Fetch Interval (minutes)</label>
        <input type="number" name="fetch_interval" min="1"
               value="{{ old('fetch_interval', $configuration->fetch_interval ?? 60) }}"
               required>
    </div>

    <div>
        <label>Status</label>
        <select name="status">
            <option value="1"
                    @selected(old('status', $configuration->status ?? 1) == 1)>
                Active
            </option>
            <option value="0"
                    @selected(old('status', $configuration->status ?? 1) == 0)>
                Inactive
            </option>
        </select>
    </div>

    <button type="submit">Save Configuration</button>
</form>
</body>
</html>
