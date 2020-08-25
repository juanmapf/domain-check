<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload success</title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <style>
    table {
        border-spacing: 0 5px;
    }
    td:first-of-type {
        border-right: 0px;
        border-radius: 5px 0 0 5px;
    }
    td:last-of-type {
        border-left: 0px;
        border-radius: 0 5px 5px 0;
    }
    </style>
    <div class="w-full p-8">
        <h1 class="text-6xl font-black tracking-wide">Checking domain <span class="text-blue-400">authority & availability</span></h1>
        <h2 id="title" class="text-lg"></h2>
        <table id="hosts" class="w-full border-separate rounded table-auto">
            <tr class="h-16">
                <th class="text-left px-4 py-2">Domain</th>
                <th class="text-center px-4 py-2 text-center">Authority</th>
            </tr>
        </table>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript">
    var hosts = <?= json_encode($upload_data) ?>;
    var total = 0;
    var totalAvailable = 0;
    var callOffset = 0;
    var done = false;
    var ready = true;
    var authStack = [];
    const perRequestLength = 25;

    /**
    * Gets the availability of all domains between offset and offset + length.
    * Returns only the available domains.
    * @params offset. The offset to begin availability "pagination"
    * @params length. How many domains per availability check
    */
    function getAvailability(offset, length = 25) {
        return $.ajax({
            url: '<?=base_url()?>/upload/get_availability',
            method: 'post',
            data: {
                hosts: hosts.slice(offset, offset + length)
            },
            dataType: 'json',
            success: function(response) {
                total += perRequestLength;
                $('#title').text(`Processed: ${total}/${hosts.length} domains. ${totalAvailable} available.`);
                var domains = [];
                response.forEach((value, index) => {
                    $('#hosts tr:last').after(makeTr(value.host.domain, value.availability));
                    domains.push(value.host.domain);
                    totalAvailable += 1;
                });
                authStack.push(...domains)
            },
            error: function(err) {
                console.log(err);
            }
        });
    }

    /**
    * Gets the authority of all passed hosts, maximum 50 (set by the used API, check https://openrank.io/).
    * On successful response, populate rank td in html.
    * @params hosts. All hosts to check authority of.
    */
    async function getAuthority(hosts) {
        const result = $.ajax({
            url: '<?=base_url()?>/upload/get_authority',
            method: 'post',
            dataType: 'json',
            data: {
                domains: hosts.join('|')
            },
            success: function(response) {
                var resp = JSON.parse(response);
                for (const host in resp.data) {
                    if (resp.data.hasOwnProperty(host)) {
                        const rank = resp.data[host].openrank;
                        $(`#${host.replace(/\./g, '')}`).children('.authority').children('.rank-cont').children('.rank').text(rank);
                        if (rank === -1) {
                            $(`#${host.replace(/\./g, '')}`).children('.authority').addClass('text-gray-900');
                            $(`#${host.replace(/\./g, '')}`).children('.authority').children('.rank-cont').addClass('bg-gray-100');
                        } else if (rank <= 25) {
                            $(`#${host.replace(/\./g, '')}`).children('.authority').addClass('text-red-600');
                            $(`#${host.replace(/\./g, '')}`).children('.authority').children('.rank-cont').addClass('bg-red-200');
                        } else if (rank <= 65) {
                            $(`#${host.replace(/\./g, '')}`).children('.authority').addClass('text-orange-600');
                            $(`#${host.replace(/\./g, '')}`).children('.authority').children('.rank-cont').addClass('bg-orange-200');
                        } else {
                            $(`#${host.replace(/\./g, '')}`).children('.authority').addClass('text-green-600');
                            $(`#${host.replace(/\./g, '')}`).children('.authority').children('.rank-cont').addClass('bg-green-200');
                        }
                    }
                }
            },
            error: function(err) {
                console.log(err);
            }
        });
        authStack = [];
        return result;
    }

    /**
    * Makes a table row identified by the host without dots.
    */
    function makeTr(host, available) {
        return `<tr id=${host.replace(/\./g, '')}><td class="text-lg mb-5 h-16 border px-4 py-2">${host}</td><td class="text-center mb-5 h-16 border px-4 py-2 authority"><span class="text-gray-900 bg-gray-100 rounded px-4 py-1 rank-cont">OR <span class="font-medium rank"></span></span></td></tr>`;
    }

    /**
    * Initializes the availability/authority call chain
    * @params offset. The offset to begin availability "pagination"
    * @params length. How many domains per availability check
    */
    function callChain(offset, length) {
        if (offset <= hosts.length) {
            // Every two availability calls (max. 50 available urls), do an authority check on available urls
            // This ensures that both, API calls remain a minimum in order to not exceed
            // rate limits and that the user gets to see authorities as soon as possible.
            $.when(
                getAvailability(offset, length),
                getAvailability(offset + length, length),
            ).done(function () {
                getAuthority(authStack);
                callChain(offset + (length * 2), length);
            });
        }
    }

    $(document).ready(function () {
        // Initialize call chain
        callChain(0, 25);
    });
    </script>
</body>
</html>