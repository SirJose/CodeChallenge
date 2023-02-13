@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="row py-5">
            <div class="col">

                {{-- PAGE TITLE --}}
                <h1 class="display-6 text-center pb-5">Code challenge Guatemala</h1>

                {{-- DROPDOWN --}}
                <div class="dropdown text-right pb-2">
                    <button id="option_dropdown" class="btn btn-secondary shadow btn-lg dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                        {{ $productOptions[$selectedOption]["name"] }}
                    </button>
                    <div class="dropdown-menu">
                        @if (count($productOptions))
                            @foreach ($productOptions as $optionId => $option)
                                <a href="#" id="option_{{ $option["code"]."_".$option["name"] }}" class="dropdown-item">{{ $optionId }}</a>
                            @endforeach
                        @else
                            <a href="#" class="dropdown-item disabled">No options available</a>
                        @endif
                    </div>
                </div>

                {{-- DESIGNS TABLE --}}
                <table class="table">

                    {{-- DESIGNS HEADER --}}
                    <thead class="thead-dark shadow">
                        <tr >
                            <th class="designs-header pl-4">Thumbnail</th>
                            <th class="designs-header pl-4">Title</th>
                            <th class="designs-header">Price</th>
                        </tr>
                    </thead>

                    {{-- DESIGNS LIST --}}
                    <tbody>
                        @if (count($designsList))
                            @foreach ($designsList as $designKey => $design)
                                <tr class="{{ $designKey === 3 ? "bg-warning":"" }} shadow-sm">
                                    {{-- THUMBNAIL --}}
                                    <td>
                                        <form action="{{ route('imagePDF') }}" method="post" target="_blank">
                                            {{ csrf_field() }}
                                            <input type="hidden" name="image_name" value="{{ $design["title"] }}">
                                            <input type="hidden" name="image_url" value="{{ $design["print_url"] }}">
                                            <input
                                                type="image"
                                                class="design-thumb shadow"
                                                src="data:image/jpeg;base64,{{ $design["thumb_base64"] }}"
                                                alt="{{ $design["alt_tag"] }}" >
                                        </form>
                                    </td>

                                    {{-- TITLE --}}
                                    <td>
                                        <div class="design-title">{{ $design["title"] }}</div>
                                    </td>

                                    {{-- PRICE --}}
                                    <td>
                                        <div class="design-price">
                                            {{-- CURRENCY SYMBOL --}}
                                            {!! $currency["html"] !!}
                                            {{-- PRICE VALUE --}}
                                            <span id="design_finalprice_{{ $design["id"] }}">
                                                {{ $design["price"]+$design["greetcard_options"]["Envelope"]["price"] }}
                                            </span>
                                        </div>
                                        <input
                                            type="hidden"
                                            id="price_design_base_{{ $design["id"] }}"
                                            value="{{ $design["price"] }}" >
                                        @foreach ($design["greetcard_options"] as $option)
                                            <input
                                                type="hidden"
                                                id="price_design_{{ $option["option_code"]."_".$design["id"] }}"
                                                value="{{ $option["price"] }}" >
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>No designs available.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endsection


@section('scripts')
    <script type="module">
        $(document).ready(function () {

            function localJsonpCallback(json){
                console.log(json);
            }

            // DROP DOWN ITEM CLICK
            $(document).on('click','.dropdown-item', function(){

                // Get selected option code and name
                const optionId = $(this).attr("id").split("_");
                const optionCode = optionId[1];
                const optionName = optionId[2];

                // Update option name in dropdown buttoon
                $("#option_dropdown").html(optionName);

                // Update price for each design
                $("span[id^='design_finalprice_']").each(function(){

                    const designId = $(this).attr("id").split("_")[2];

                    $.ajax({
                        url: '/option-price',
                        method: 'POST',
                        data: {
                            _token: $('input[name="_token"]').val(),
                            store_id: designId,
                            option_name: optionName
                        },
                        success: function(data){
                            const optionData = $.parseJSON(data);

                            // Add option price to base price
                            const basePrice = parseFloat($(`#price_design_base_${designId}`).val());
                            const optionPrice = parseFloat(optionData.option_price);
                            const updatedPrice  = (basePrice + optionPrice).toFixed(2);

                            // Update price value in DOM
                            $(`#design_finalprice_${optionData.design_id}`).html(updatedPrice);
                        }
                    });

                });
            });

        });
    </script>
@endsection
