    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const base = {
                    chart: {
                        fontFamily: "'Plus Jakarta Sans', sans-serif",
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        fontWeight: 600
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '11px',
                            fontWeight: '700'
                        }
                    },
                    tooltip: {
                        theme: 'light'
                    },
                };

                @if (auth()->user()->role === 'admin')
                    new ApexCharts(document.querySelector("#adminChart"), {
                        ...base,
                        series: @json($data['chart_role_data']),
                        labels: @json($data['chart_role_labels']),
                        chart: {
                            ...base.chart,
                            type: 'donut',
                            height: 280
                        },
                        colors: ['#4f7fff', '#06b6d4', '#6b7280'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Tổng',
                                            fontSize: '12px',
                                            fontWeight: '800'
                                        }
                                    }
                                }
                            }
                        },
                        stroke: {
                            width: 0
                        },
                    }).render();
                @elseif (auth()->user()->role === 'teacher')
                    new ApexCharts(document.querySelector("#teacherChart"), {
                        ...base,
                        series: @json($data['chart_submission_data']),
                        labels: @json($data['chart_submission_labels']),
                        chart: {
                            ...base.chart,
                            type: 'donut',
                            height: 280
                        },
                        colors: ['#10b981', '#f43f5e'],
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%'
                                }
                            }
                        },
                        stroke: {
                            width: 0
                        },
                    }).render();
                @else
                    @if (count($data['chart_quiz_data']) > 0)
                        new ApexCharts(document.querySelector("#studentChart"), {
                            ...base,
                            series: [{
                                name: 'Điểm số',
                                data: @json($data['chart_quiz_data'])
                            }],
                            chart: {
                                ...base.chart,
                                type: 'bar',
                                height: 300
                            },
                            xaxis: {
                                categories: @json($data['chart_quiz_labels']),
                                labels: {
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: '600'
                                    }
                                }
                            },
                            yaxis: {
                                max: 10,
                                tickAmount: 5,
                                labels: {
                                    style: {
                                        fontSize: '12px'
                                    }
                                }
                            },
                            colors: ['#8b5cf6'],
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shade: 'light',
                                    type: 'vertical',
                                    shadeIntensity: .2,
                                    gradientToColors: ['#4f7fff'],
                                    opacityFrom: 1,
                                    opacityTo: .85
                                }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 8,
                                    columnWidth: '42%'
                                }
                            },
                            grid: {
                                borderColor: '#f0f0f0',
                                strokeDashArray: 4
                            },
                        }).render();
                    @endif
                @endif
            });
        </script>
    @endpush
