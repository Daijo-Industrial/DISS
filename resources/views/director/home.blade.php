@extends('new.layouts.app')

@section('page-title', 'Director Dashboard')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6">
        @include('partials.alert-success-error')



        {{-- Main Dashboard --}}
        <div id="view-1">
            <div class="grid md:grid-cols-2 gap-6">
                {{-- QA/QC Reports --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 pb-0">
                        <h3 class="text-xl font-light text-slate-600">QA/QC Reports</h3>
                    </div>
                    <hr class="my-4">
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-3 gap-4">
                            <a href="{{ route('director.qaqc.index') }}" class="block">
                                <x-card title="Approved" :content="$reportCounts['approved']" color="green" titleColor="text-green-600"
                                    icon="<box-icon name='check' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('director.qaqc.index') }}" class="block">
                                <x-card title="Waiting" :content="$reportCounts['waiting']" color="orange" titleColor="text-amber-600"
                                    icon="<box-icon name='time' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('director.qaqc.index') }}" class="block">
                                <x-card title="Rejected" :content="$reportCounts['rejected']" color="red" titleColor="text-red-600"
                                    contentColor="text-slate-600"
                                    icon="<box-icon name='x-circle' color='gray' size='lg'></box-icon>" />
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Purchase Requests --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 pb-0">
                        <h3 class="text-xl text-slate-600">Purchase Requests</h3>
                    </div>
                    <hr class="my-4">
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-3 gap-4">
                            <a href="{{ route('director.pr.index') }}" class="block">
                                <x-card title="Approved" :content="$purchaseRequestCounts['approved']" color="green" titleColor="text-green-600"
                                    icon="<box-icon name='check' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('director.pr.index') }}" class="block">
                                <x-card title="Waiting" :content="$purchaseRequestCounts['waiting']" color="orange" titleColor="text-amber-600"
                                    icon="<box-icon name='time' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('director.pr.index') }}" class="block">
                                <x-card title="Rejected" :content="$purchaseRequestCounts['rejected']" color="red" titleColor="text-red-600"
                                    contentColor="text-slate-600"
                                    icon="<box-icon name='x-circle' color='gray' size='lg'></box-icon>" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mt-6">
                {{-- Monthly Budget Reports --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 pb-0">
                        <h3 class="text-xl font-light text-slate-600">Monthly Budget Reports</h3>
                    </div>
                    <hr class="my-4">
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-3 gap-4">
                            <a href="{{ route('monthly.budget.report.index') }}" class="block">
                                <x-card title="Approved" :content="$monthlyBudgetReportsCounts['approved']" color="green" titleColor="text-green-600"
                                    icon="<box-icon name='check' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('monthly.budget.report.index') }}" class="block">
                                <x-card title="Waiting" :content="$monthlyBudgetReportsCounts['waiting']" color="orange" titleColor="text-amber-600"
                                    icon="<box-icon name='time' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('monthly.budget.report.index') }}" class="block">
                                <x-card title="Rejected" :content="$monthlyBudgetReportsCounts['rejected']" color="red" titleColor="text-red-600"
                                    contentColor="text-slate-600"
                                    icon="<box-icon name='x-circle' color='gray' size='lg'></box-icon>" />
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Monthly Budget Summary Reports --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 pb-0">
                        <h3 class="text-xl text-slate-600">Monthly Budget Summary Reports</h3>
                    </div>
                    <hr class="my-4">
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-3 gap-4">
                            <a href="{{ route('monthly-budget-summary.index') }}" class="block">
                                <x-card title="Approved" :content="$monthlyBudgetSummaryReportsCounts['approved']" color="green" titleColor="text-green-600"
                                    icon="<box-icon name='check' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('monthly-budget-summary.index') }}" class="block">
                                <x-card title="Waiting" :content="$monthlyBudgetSummaryReportsCounts['waiting']" color="orange" titleColor="text-amber-600"
                                    icon="<box-icon name='time' color='gray' size='lg'></box-icon>" />
                            </a>
                            <a href="{{ route('monthly-budget-summary.index') }}" class="block">
                                <x-card title="Rejected" :content="$monthlyBudgetSummaryReportsCounts['rejected']" color="red" titleColor="text-red-600"
                                    contentColor="text-slate-600"
                                    icon="<box-icon name='x-circle' color='gray' size='lg'></box-icon>" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Purchase Order Reports --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm mt-6">
                <div class="p-6 pb-0">
                    <h3 class="text-xl text-slate-600">Purchase Order Reports</h3>
                </div>
                <hr class="my-4">
                <div class="px-6 pb-6">
                    <div class="grid grid-cols-3 gap-4">
                        <a href="{{ route('po.dashboard') }}" class="block">
                            <x-card title="Approved" :content="$poCounts['approved']" color="green" titleColor="text-green-600"
                                icon="<box-icon name='check' color='gray' size='lg'></box-icon>" />
                        </a>
                        <a href="{{ route('po.dashboard') }}" class="block">
                            <x-card title="Waiting" :content="$poCounts['waiting']" color="orange" titleColor="text-amber-600"
                                icon="<box-icon name='time' color='gray' size='lg'></box-icon>" />
                        </a>
                        <a href="{{ route('po.dashboard') }}" class="block">
                            <x-card title="Rejected" :content="$poCounts['rejected']" color="red" titleColor="text-red-600"
                                contentColor="text-slate-600"
                                icon="<box-icon name='x-circle' color='gray' size='lg'></box-icon>" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
