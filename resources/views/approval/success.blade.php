<!-- resources/views/approval/success.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Response Recorded</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h1 class="text-center">Reservation Response Recorded</h1>
                    </div>
                    <div class="card-body">
                        @if(session('message'))
                            <div class="alert alert-success">
                                {{ session('message') }}
                            </div>
                        @else
                            <p class="text-center">Your response to the reservation has been successfully recorded.</p>
                        @endif

                        {{-- <div class="mt-4 text-center">
                            <a href="{{ url('/') }}" class="btn btn-primary">Return to Homepage</a>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>