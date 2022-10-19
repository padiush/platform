@if(session()->has('message'))
<div class="pt-4 animate__animated animate__pulse animate__repeat-2" id="alert">
    <div class="alert shadow-lg max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div>
            <i class="fa-solid fa-circle-info mr-2"></i>
            <span>{{ session()->get('message') }}</span>
            <!-- Close alert icon -->
            <span onclick="closeAlert()">
                <i class="fa-solid fa-times ml-2 cursor-pointer"></i>
            </span>
        </div>
    </div>
</div>
@endif

@if(session()->has('success'))
<div class="pt-4 animate__animated animate__pulse animate__repeat-2" id="alert">
    <div class="alert alert-success shadow-lg max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div>
            <i class="fa-solid fa-circle-check mr-2"></i>
            <span>{{ session()->get('success') }}</span>
            <!-- Close alert icon -->
            <span onclick="closeAlert()">
                <i class="fa-solid fa-times ml-2 cursor-pointer"></i>
            </span>
        </div>
    </div>
</div>
@endif

@if(session()->has('error'))
<div class="pt-4 animate__animated animate__pulse animate__repeat-2" id="alert">
    <div class="alert alert-error shadow-lg max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div>
            <i class="fa-solid fa-circle-x mr-2"></i>
            <span>{{ session()->get('error') }}</span>
            <!-- Close alert icon -->
            <span onclick="closeAlert()">
                <i class="fa-solid fa-times ml-2 cursor-pointer"></i>
            </span>
        </div>
    </div>
</div>
@endif