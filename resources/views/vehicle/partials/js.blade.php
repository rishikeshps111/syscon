<script>
    $(function () {
        $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('vehicles.index') }}",
                data: filters
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'vehicle_no', name: 'vehicle_no', className: 'text-center' },
                { data: 'type', name: 'vehicle_type', className: 'text-center' },
                { data: 'fuel', name: 'fuel_type', className: 'text-center' },
                { data: 'oem_name', name: 'oem.oem_name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'capacity', name: 'capacity_seating', orderable: false, searchable: false, className: 'text-center' },
                { data: 'insurance_expiry_badge', name: 'insurance_expiry', className: 'text-center' },
                { data: 'fitness_expiry_badge', name: 'fitness_expiry', className: 'text-center' },
                { data: 'gps_status', name: 'gps_enabled', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: []
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 300);
        });

        $('#searchFilters').on('click', reloadTable);
        $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').on('change', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter, #searchFilter').val('');
            $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').trigger('change.select2');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadTable();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('change', '.row-check', function () {
            $('#checkAll').prop('checked', $('.row-check').length === $('.row-check:checked').length);
        });

        $(document).on('click', '.change-status-btn', function () {
            $('#changeStatusForm').attr('action', $(this).data('url'));
            $('#modalStatus').val($(this).data('status'));
            $('#changeStatusModal').modal('show');
        });

        $(document).on('click', '.view-vehicle-qr', function (event) {
            event.preventDefault();

            $.ajax({
                url: $(this).data('url'),
                type: 'GET',
                success: function (res) {
                    showVehicleQrAlert(res);
                },
                error: function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Unable to generate vehicle QR.');
                }
            });
        });

        $('#changeStatusForm').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnHtml = submitBtn.html();
            form.find('.error-text').text('');
            submitBtn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#changeStatusModal').modal('hide');
                    table.ajax.reload();
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            form.find('.' + field + '_error').text(messages[0]);
                        });
                    } else {
                        showToast('error', xhr.responseJSON?.message || 'Something went wrong');
                    }
                },
                complete: function () {
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });

        $('#exportSelected').on('click', function () {
            let selectedIds = [];
            $('.row-check:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }

            $.ajax({
                url: "{{ route('vehicles.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    search_text: $('#searchFilter').val(),
                    state_id: $('#stateFilter').val(),
                    oem_id: $('#oemFilter').val(),
                    vehicle_type: $('#vehicleTypeFilter').val(),
                    fuel_type: $('#fuelTypeFilter').val(),
                    status: $('#statusFilter').val(),
                    gps_enabled: $('#gpsFilter').val()
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'vehicles.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    $('.row-check').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function () {
                    showToast('error', 'Export failed.');
                }
            });
        });

        function filters(data) {
            data.search_text = $('#searchFilter').val();
            data.state_id = $('#stateFilter').val();
            data.oem_id = $('#oemFilter').val();
            data.vehicle_type = $('#vehicleTypeFilter').val();
            data.fuel_type = $('#fuelTypeFilter').val();
            data.status = $('#statusFilter').val();
            data.gps_enabled = $('#gpsFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }

        function showVehicleQrAlert(data) {
            Swal.fire({
                title: 'Vehicle QR',
                html: '<div class="vehicle-qr-modal">'
                    + '<div id="vehicleQrBox" class="vehicle-qr-box">' + data.svg + '</div>'
                    + '<div class="vehicle-qr-code">Vehicle Code: <strong>' + escapeHtml(data.code) + '</strong></div>'
                    + '<div class="vehicle-qr-name">' + escapeHtml(data.name || '') + '</div>'
                    + '<div class="d-flex justify-content-center gap-2 flex-wrap mt-3">'
                    + '<button type="button" class="btn btn-sm btn-primary" id="downloadVehicleQrImage">Download Image</button>'
                    + '<button type="button" class="btn btn-sm btn-outline-primary" id="downloadVehicleQrPdf">Download PDF</button>'
                    + '</div>'
                    + '</div>',
                showCancelButton: true,
                confirmButtonText: 'Copy Vehicle Code',
                cancelButtonText: 'Close',
                width: 420,
                didOpen: function () {
                    $('#downloadVehicleQrImage').on('click', function () {
                        downloadQrImage(data);
                    });
                    $('#downloadVehicleQrPdf').on('click', function () {
                        downloadQrPdf(data);
                    });
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    copyText(data.code, 'Vehicle code copied.');
                }
            });
        }

        function downloadQrImage(data) {
            var svgBlob = new Blob([data.svg], { type: 'image/svg+xml;charset=utf-8' });
            var url = URL.createObjectURL(svgBlob);
            var image = new Image();

            image.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width = image.width || 290;
                canvas.height = image.height || 290;
                var context = canvas.getContext('2d');
                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0);
                URL.revokeObjectURL(url);

                canvas.toBlob(function (blob) {
                    if (!blob) {
                        showToast('error', 'Unable to download QR image.');
                        return;
                    }

                    downloadBlob(blob, fileSafe(data.code || 'vehicle') + '-qr.png');
                }, 'image/png');
            };

            image.onerror = function () {
                URL.revokeObjectURL(url);
                showToast('error', 'Unable to download QR image.');
            };

            image.src = url;
        }

        function downloadQrPdf(data) {
            var pdf = buildQrPdf(data);
            downloadBlob(new Blob([pdf], { type: 'application/pdf' }), fileSafe(data.code || 'vehicle') + '-qr.pdf');
        }

        function buildQrPdf(data) {
            var qrSize = Number((data.svg.match(/viewBox="0 0 (\d+) \d+"/) || [])[1] || 290);
            var targetSize = 220;
            var left = 187;
            var top = 195;
            var pageHeight = 842;
            var scale = targetSize / qrSize;
            var content = '0.96 0.97 0.99 rg\n0 0 595 842 re f\n'
                + pdfText('SYSCON', 50, 795, 18, 'F2')
                + pdfText('Vehicle QR', 50, 765, 24, 'F2')
                + pdfText('Vehicle Code: ' + (data.code || '-'), 50, 725, 12, 'F2')
                + pdfText('Vehicle No: ' + (data.name || '-'), 50, 705, 11, 'F1')
                + '1 1 1 rg\n172 397 250 270 re f\n0.84 0.86 0.90 RG\n172 397 250 270 re S\n'
                + qrPdfRects(data.svg, left, top, pageHeight, scale)
                + pdfText('Scan this QR to identify the vehicle.', 194, 425, 10, 'F1');

            return pdfDocument(content);
        }

        function qrPdfRects(svg, left, top, pageHeight, scale) {
            var path = (svg.match(/<path[^>]* d="([^"]+)"/) || [])[1] || '';
            var regex = /M(\d+) (\d+)h(\d+)v(\d+)h-\d+z/g;
            var match;
            var content = '0 0 0 rg\n';

            while ((match = regex.exec(path)) !== null) {
                var x = left + (Number(match[1]) * scale);
                var y = pageHeight - top - ((Number(match[2]) + Number(match[4])) * scale);
                var size = Number(match[3]) * scale;
                content += x.toFixed(2) + ' ' + y.toFixed(2) + ' ' + size.toFixed(2) + ' ' + size.toFixed(2) + ' re f\n';
            }

            return content;
        }

        function pdfDocument(content) {
            var objects = [
                '1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n',
                '2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n',
                '3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>\nendobj\n',
                '4 0 obj\n<< /Length ' + content.length + ' >>\nstream\n' + content + 'endstream\nendobj\n',
                '5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n',
                '6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n'
            ];
            var pdf = '%PDF-1.4\n';
            var offsets = [0];

            objects.forEach(function (object) {
                offsets.push(pdf.length);
                pdf += object;
            });

            var xref = pdf.length;
            pdf += 'xref\n0 7\n0000000000 65535 f \n';
            offsets.slice(1).forEach(function (offset) {
                pdf += String(offset).padStart(10, '0') + ' 00000 n \n';
            });

            return pdf + 'trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n' + xref + '\n%%EOF';
        }

        function pdfText(text, x, y, size, font) {
            return '0.08 0.10 0.14 rg\nBT\n/' + font + ' ' + size + ' Tf\n' + x + ' ' + y + ' Td\n(' + pdfEscape(text).slice(0, 90) + ') Tj\nET\n';
        }

        function pdfEscape(value) {
            return String(value).replace(/[^\x20-\x7E]/g, '').replace(/[\\()]/g, '\\$&');
        }

        function downloadBlob(blob, fileName) {
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        function fileSafe(value) {
            return String(value).replace(/[^A-Za-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '') || 'vehicle';
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function copyText(value, message) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(function () {
                    showToast('success', message);
                });

                return;
            }

            var input = document.createElement('textarea');
            input.value = value;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast('success', message);
        }
    });

    function deleteRow(id) {
        deleteRecord('/vehicles/' + id, 'table', 'Do you really want to delete this vehicle?');
    }
</script>
