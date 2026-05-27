(function () {
    /* ========= sidebar toggle ======== */
    const sidebarNavWrapper = document.querySelector(".sidebar-nav-wrapper");
    const mainWrapper = document.querySelector(".main-wrapper");
    const menuToggleButton = document.querySelector("#menu-toggle");
    const menuToggleButtonIcon = document.querySelector("#menu-toggle i");
    const overlay = document.querySelector(".overlay");

    menuToggleButton.addEventListener("click", () => {
        sidebarNavWrapper.classList.toggle("active");
        overlay.classList.add("active");
        mainWrapper.classList.toggle("active");

        if (document.body.clientWidth > 1200) {
            if (menuToggleButtonIcon.classList.contains("lni-chevron-left")) {
                menuToggleButtonIcon.classList.remove("lni-chevron-left");
                menuToggleButtonIcon.classList.add("lni-menu");
            } else {
                menuToggleButtonIcon.classList.remove("lni-menu");
                menuToggleButtonIcon.classList.add("lni-chevron-left");
            }
        } else {
            if (menuToggleButtonIcon.classList.contains("lni-chevron-left")) {
                menuToggleButtonIcon.classList.remove("lni-chevron-left");
                menuToggleButtonIcon.classList.add("lni-menu");
            }
        }
    });
    overlay.addEventListener("click", () => {
        sidebarNavWrapper.classList.remove("active");
        overlay.classList.remove("active");
        mainWrapper.classList.remove("active");
    });
})();

jQuery(document).ready(function ($) {
    if (!document.getElementById("Chart1")) return;

    const ctx1 = document.getElementById("Chart1").getContext("2d");

    const chart1 = new Chart(ctx1, {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: chartData.ad_views,
                backgroundColor: "transparent",
                borderColor: "#242424",
                data: [],
                pointBackgroundColor: "transparent",
                pointHoverBackgroundColor: "#242424",
                pointBorderColor: "transparent",
                pointHoverBorderColor: "#fff",
                pointHoverBorderWidth: 5,
                pointBorderWidth: 5,
                pointRadius: 8,
                pointHoverRadius: 8,
            }],
        },
        options: {
            tooltips: {
                callbacks: {
                    labelColor: () => ({ backgroundColor: "#ffffff" })
                },
                intersect: false,
                backgroundColor: "#f9f9f9",
                titleFontColor: "#8F92A1",
                titleFontSize: 12,
                bodyFontColor: "#171717",
                bodyFontStyle: "bold",
                bodyFontSize: 16,
                displayColors: false,
                xPadding: 30,
                yPadding: 10,
                bodyAlign: "center",
                titleAlign: "center",
            },
            scales: {
                yAxes: [{
                    gridLines: {display: false, drawTicks: false, drawBorder: false},
                    ticks: {padding: 35, min: 0},
                }],
                xAxes: [{
                    gridLines: {
                        display: false, drawBorder: false,
                        color: "rgba(143,146,161,0.1)",
                        zeroLineColor: "rgba(143,146,161,0.1)"
                    },
                    ticks: {padding: 20},
                }],
            },
        }
    });

    function fetchAndRenderChart(period = 'yearly', security='') {
        const loadingIndicator = $('#chart-loading-dash');
        $('#Chart1').css('opacity', '0.5');
        loadingIndicator.show();

        $.ajax({
            url: chartData.ajax_url,
            method: 'POST',
            data: { 
                action: 'get_ad_counts',
                period: period,
                security: security 
            },
            dataType: 'json',
            success: function (res) {
                loadingIndicator.hide();
                $('#Chart1').css('opacity', '1');

                if (!res.success) {
                    console.error("Failed to load Chart data.");
                    return;
                }

                const vals = res.data;
                if (period === 'yearly') {
                    chart1.data.labels = chartData.months;
                }
                else if (period === 'monthly') {
                    chart1.data.labels = Array.from({length: vals.length}, (_, i) => i + 1);
                }
                else {
                    chart1.data.labels = chartData.weeks;
                }

                chart1.data.datasets[0].data = vals;
                chart1.update();
            },
            error: function (xhr, status, error) {
                loadingIndicator.hide();
                $('#Chart1').css('opacity', '1');
                console.error("Error fetching chart data:", error);
            }
        });
    }

    var nonce = $("#posted_ads_graph_select").data('security-nonce');

    fetchAndRenderChart('weekly', nonce);

    $("#posted_ads_graph_select").on('change', function () {
        var nonce = $(this).data('security-nonce');
        fetchAndRenderChart(this.value, nonce);
    });
});

jQuery(document).ready(function ($) {
    if (!document.getElementById("ProfileViewsChart")) {
        return;
    }

    let rawData = $('#profile-views-chart-container').attr('data-views') || '{}';
    try {
        rawData = JSON.parse(rawData);
    } catch (e) {
        console.error("Could not parse profile-views JSON:", e);
        rawData = {};
    }

    const ctx = document.getElementById("ProfileViewsChart").getContext("2d");
    const chart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: [],
            datasets: [{
                label: chartData.profile_views,
                backgroundColor: "#242424",
                barThickness: 8,
                maxBarThickness: 12,
                data: [],
            }],
        },
        options: {
            borderColor: "#F3F6F8",
            borderWidth: 15,
            backgroundColor: "#F3F6F8",
            tooltips: {
                callbacks: {labelColor: () => ({backgroundColor: "rgba(104,110,255,0)"})},
                backgroundColor: "#F3F6F8",
                titleFontColor: "#8F92A1",
                titleFontSize: 12,
                bodyFontColor: "#171717",
                bodyFontStyle: "bold",
                bodyFontSize: 16,
                multiKeyBackground: "transparent",
                displayColors: false,
                xPadding: 30,
                yPadding: 10,
                bodyAlign: "center",
                titleAlign: "center",
            },
            title: {display: false},
            legend: {display: false},
            scales: {
                yAxes: [{
                    gridLines: {display: false, drawTicks: false, drawBorder: false},
                    ticks: {padding: 35, min: 0},
                }],
                xAxes: [{
                    gridLines: {
                        display: false, drawBorder: false,
                        color: "rgba(143,146,161,0.1)",
                        zeroLineColor: "rgba(143,146,161,0.1)"
                    },
                    ticks: {padding: 20},
                }],
            },
        },
    });

    function setLabels(period) {
        if (period === 'yearly') {
            chart.data.labels = chartData.months;
        } else if (period === 'monthly') {
            chart.data.labels = Array.from({length: 31}, (_, i) => i + 1);
        } else if (period === 'weekly') {
            chart.data.labels = chartData.weeks;
        }
    }

    function aggregate(period) {
        const buckets = {};
        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth();

        if (period === 'yearly') {
            for (let m = 0; m < 12; m++) buckets[m] = 0;

            Object.entries(rawData).forEach(([dateStr, count]) => {
                const d = new Date(dateStr);
                if (d.getFullYear() === currentYear) {
                    buckets[d.getMonth()] += count;
                }
            });

            return Object.keys(buckets).map(k => buckets[k]);
        }

        if (period === 'monthly') {
            for (let d = 1; d <= 31; d++) buckets[d] = 0;

            Object.entries(rawData).forEach(([dateStr, count]) => {
                const d = new Date(dateStr);
                if (d.getFullYear() === currentYear && d.getMonth() === currentMonth) {
                    buckets[d.getDate()] += count;
                }
            });

            return Array.from({length: 31}, (_, i) => buckets[i + 1] || 0);
        }

        if (period === 'weekly') {
            for (let w = 0; w < 7; w++) buckets[w] = 0;

            const currentWeekday = (today.getDay() + 6) % 7;
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - currentWeekday);
            startOfWeek.setHours(0, 0, 0, 0);

            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            endOfWeek.setHours(23, 59, 59, 999);

            Object.entries(rawData).forEach(([dateStr, count]) => {
                const d = new Date(dateStr);
                if (d >= startOfWeek && d <= endOfWeek) {
                    const weekdayIndex = (d.getDay() + 6) % 7;
                    buckets[weekdayIndex] += count;
                }
            });

            return Object.keys(buckets).map(k => buckets[k]);
        }
    }

    function render(period) {
        $('#ProfileViewsChart').css('opacity', '0.5');
        $('#chart-loading-profile-views').show();

        setLabels(period);
        const data = aggregate(period);
        chart.data.datasets[0].data = data;
        chart.update();

        $('#ProfileViewsChart').css('opacity', '1');
        $('#chart-loading-profile-views').hide();
    }

    $('#sold_ads_graph_select').on('change', function () {
        render($(this).val());
    });

    render('weekly');
});

jQuery(document).ready(function ($) {
    const userDpContainer = document.querySelector('.user-dp-container');
    const fileInput = document.getElementById('imgInp');

    if (userDpContainer) {
        userDpContainer.addEventListener('click', function () {
            fileInput.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function (event) {
            const [file] = fileInput.files;
            if (file) {
                document.getElementById('img-upload').src = URL.createObjectURL(file);
            }
        });
    }

});