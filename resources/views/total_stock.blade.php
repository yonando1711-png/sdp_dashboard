@extends('layouts.app')

@section('title', 'Total Stock - Advanced Search')

@section('content')
<div x-data="queryBuilder()" x-init="init()" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden h-[calc(100vh-140px)] flex flex-col">
    
    <!-- Top Bar -->
    <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-indigo-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                Total Stock
                <span class="text-sm font-normal text-slate-500 bg-slate-100 px-2 py-1 rounded ml-2">Advanced Filter</span>
            </h1>
            <p class="text-slate-500 text-sm mt-1">Found <span x-text="totalItems" class="font-bold text-indigo-600"></span> records matching criteria</p>
        </div>

        <div class="flex gap-2">
            <!-- Columns Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                    Cols
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-100 py-2 z-20 max-h-96 overflow-y-auto">
                    <template x-for="(col, id) in columns" :key="id">
                        <label class="flex items-center px-4 py-2 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="col.visible" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mr-2">
                            <span class="text-sm text-slate-700" x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </div>

            <button @click="fetchData()" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-md shadow-indigo-200 font-medium">
                <svg x-show="!isLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <svg x-show="isLoading" class="animate-spin -ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="isLoading ? 'Searching...' : 'Apply Filters'"></span>
            </button>
        </div>
    </div>

    <div class="flex flex-col h-full">
        <!-- Sidebar: Query Builder -->
        <div class="w-full bg-slate-50 border-b border-slate-200 p-4 overflow-y-auto min-h-[200px] max-h-[40vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-slate-700">Filter Logic</h3>
                <button @click="resetQuery()" class="text-xs text-red-500 hover:text-red-700 hover:underline">Reset All</button>
            </div>

            <!-- Recursive Group Component -->
            <template x-component="filter-group">
                <div class="ml-4 pl-4 border-l-2 border-slate-200 relative mb-2">
                    <!-- Logic Operator Toggle -->
                    <div class="absolute -left-3 top-0">
                        <button @click="group.operator = group.operator === 'AND' ? 'OR' : 'AND'" 
                                class="text-xs font-bold px-1.5 py-0.5 rounded border shadow-sm transition-colors w-10"
                                :class="group.operator === 'AND' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-amber-100 text-amber-700 border-amber-200'"
                                x-text="group.operator">
                        </button>
                    </div>

                    <!-- Rules List -->
                    <div class="space-y-3 pt-6">
                        <template x-for="(rule, index) in group.rules" :key="index">
                            <div class="relative bg-white p-3 rounded-lg border border-slate-200 shadow-sm group">
                                <!-- Remove Button -->
                                <button @click="removeRule(group, index)" class="absolute -right-2 -top-2 bg-white rounded-full text-red-400 hover:text-red-600 shadow-sm border border-slate-200 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>

                                <template x-if="rule.rules">
                                    <div x-data="{ group: rule }" x-bind="filterGroup">
                                         <div class="text-xs text-slate-400">Nested Group</div>
                                    </div>
                                </template>

                                <!-- Condition Rule -->
                                <template x-if="!rule.rules">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                        <!-- Field -->
                                        <select x-model="rule.field" class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            <optgroup label="General">
                                                <option value="lot_number">Lot Number</option>
                                                <option value="product">Product</option>
                                                <option value="category">Category (Logic)</option>
                                            </optgroup>
                                            <optgroup label="Inventory">
                                                <option value="location">Location</option>
                                                <option value="on_hand_quantity">Qty</option>
                                            </optgroup>
                                            <optgroup label="Rental Info">
                                                <option value="rental_id">Rental ID</option>
                                                <option value="rental_type">Rental Type</option>
                                                <option value="vehicle_role">Role</option>
                                                <option value="rental_id_count">Rental Count</option>
                                            </optgroup>
                                        </select>
                                        
                                        <!-- Operator (Hidden for Category) -->
                                        <select x-show="rule.field !== 'category'" x-model="rule.operator" class="w-full text-sm border-slate-200 rounded-md bg-slate-50">
                                            <option value="contains">Contains</option>
                                            <option value="not_contains">Not Contains</option>
                                            <option value="=">Equals (=)</option>
                                            <option value="!=">Not Equals (!=)</option>
                                            <option value="starts_with">Starts With</option>
                                            <option value="ends_with">Ends With</option>
                                            <option value="is_empty">Is Empty</option>
                                            <option value="is_not_empty">Is Not Empty</option>
                                        </select>
                                        
                                        <!-- Fixed 'IS' for Category -->
                                        <div x-show="rule.field === 'category'" class="flex items-center justify-center text-sm font-bold text-slate-400 bg-slate-50 rounded-md border border-slate-200">
                                            IS
                                        </div>

                                        <!-- Value Input -->
                                        <div x-show="rule.field !== 'category' && !['is_empty', 'is_not_empty'].includes(rule.operator)">
                                            <input x-model="rule.value" 
                                                   type="text" 
                                                   placeholder="Value..." 
                                                   class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        </div>

                                        <!-- Category Select -->
                                        <div x-show="rule.field === 'category'">
                                            <select x-model="rule.value" class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="" disabled>Select Category</option>
                                                <optgroup label="In Stock">
                                                    <option value="in_stock">In Stock (All)</option>
                                                    <option value="stock_pure">Pure Stock (No Rental)</option>
                                                    <option value="stock_reserve">Reserve Stock</option>
                                                    <option value="stock_original_with_replace">Original w/ Replacement</option>
                                                    <option value="stock_original_no_replace">Original w/o Replacement</option>
                                                </optgroup>
                                                <optgroup label="Rented">
                                                    <option value="rented_visual">Rented in Customer (All)</option>
                                                    <option value="rented_original">Original in Customer</option>
                                                    <option value="rented_replacement_service">Replacement - Service</option>
                                                    <option value="rented_replacement_rbo">Replacement - RBO</option>
                                                    <option value="rented_check_position">Check Rent Position</option>
                                                    <option value="vendor_rent">Vendor Rent</option>
                                                </optgroup>
                                                <optgroup label="Service (External)">
                                                    <option value="service_external">External (All)</option>
                                                    <option value="service_external_original_with_replace">Ext: Original w/ Replace</option>
                                                    <option value="service_external_original_no_replace">Ext: Original w/o Replace</option>
                                                    <option value="service_external_rented_replacement">Ext: Rented Replacement</option>
                                                    <option value="service_external_stock">Ext: Stock In Service</option>
                                                </optgroup>
                                                <optgroup label="Service (Internal)">
                                                    <option value="service_internal">Internal (All)</option>
                                                    <option value="service_internal_original_with_replace">Int: Original w/ Replace</option>
                                                    <option value="service_internal_original_no_replace">Int: Original w/o Replace</option>
                                                    <option value="service_internal_rented_replacement">Int: Rented Replacement</option>
                                                    <option value="service_internal_stock">Int: Stock In Service</option>
                                                </optgroup>
                                                <optgroup label="Insurance">
                                                    <option value="service_insurance">Insurance (All)</option>
                                                    <option value="service_insurance_original_with_replace">Ins: Original w/ Replace</option>
                                                    <option value="service_insurance_original_no_replace">Ins: Original w/o Replace</option>
                                                    <option value="service_insurance_rented_replacement">Ins: Rented Replacement</option>
                                                    <option value="service_insurance_stock">Ins: Stock In Service</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Add Buttons -->
                    <div class="mt-3 flex gap-2">
                        <button @click="addRule(group)" class="text-xs bg-white border border-slate-300 px-2 py-1 rounded hover:bg-slate-50">+ Condition</button>
                    </div>
                </div>
            </template>
            
            <!-- Root Group Wrapper -->
            <!-- We manually reproduce the template inner HTML here since x-template recursion is limited -->
            <div class="ml-2 pl-4 border-l-2 border-slate-200 relative mb-2">
                <div class="absolute -left-3 top-0">
                    <button @click="query.operator = query.operator === 'AND' ? 'OR' : 'AND'" 
                            class="text-xs font-bold px-1.5 py-0.5 rounded border shadow-sm transition-colors w-10"
                            :class="query.operator === 'AND' ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-amber-100 text-amber-700 border-amber-200'"
                            x-text="query.operator">
                    </button>
                </div>
                <div class="space-y-3 pt-6">
                    <template x-for="(rule, index) in query.rules" :key="index">
                        <div class="relative bg-white p-3 rounded-lg border border-slate-200 shadow-sm group">
                            <button @click="removeRule(query, index)" class="absolute -right-2 -top-2 bg-white rounded-full text-red-400 hover:text-red-600 shadow-sm border border-slate-200 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <select x-model="rule.field" class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <optgroup label="General">
                                        <option value="lot_number">Lot Number</option>
                                        <option value="product">Product</option>
                                        <option value="category">Category (Logic)</option>
                                    </optgroup>
                                    <optgroup label="Inventory">
                                        <option value="location">Location</option>
                                        <option value="on_hand_quantity">Qty</option>
                                    </optgroup>
                                    <optgroup label="Rental Info">
                                        <option value="rental_id">Rental ID</option>
                                        <option value="rental_type">Rental Type</option>
                                        <option value="vehicle_role">Role</option>
                                        <option value="rental_id_count">Rental Count</option>
                                    </optgroup>
                                </select>
                                
                                <select x-show="rule.field !== 'category'" x-model="rule.operator" class="w-full text-sm border-slate-200 rounded-md bg-slate-50">
                                    <option value="contains">Contains</option>
                                    <option value="not_contains">Not Contains</option>
                                    <option value="=">Equals (=)</option>
                                    <option value="!=">Not Equals (!=)</option>
                                    <option value="starts_with">Starts With</option>
                                    <option value="ends_with">Ends With</option>
                                    <option value="is_empty">Is Empty</option>
                                    <option value="is_not_empty">Is Not Empty</option>
                                </select>
                                <div x-show="rule.field === 'category'" class="flex items-center justify-center text-sm font-bold text-slate-400 bg-slate-50 rounded-md border border-slate-200">
                                    IS
                                </div>
                                
                                <div x-show="rule.field !== 'category' && !['is_empty', 'is_not_empty'].includes(rule.operator)">
                                    <input x-model="rule.value" type="text" placeholder="Value..." class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div x-show="rule.field === 'category'">
                                    <select x-model="rule.value" class="w-full text-sm border-slate-200 rounded-md focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        <option value="" disabled>Select Category</option>
                                        <optgroup label="In Stock">
                                            <option value="in_stock">In Stock (All)</option>
                                            <option value="stock_pure">Pure Stock (No Rental)</option>
                                            <option value="stock_reserve">Reserve Stock</option>
                                            <option value="stock_original_with_replace">Original w/ Replacement</option>
                                            <option value="stock_original_no_replace">Original w/o Replacement</option>
                                        </optgroup>
                                        <optgroup label="Rented">
                                            <option value="rented_visual">Rented in Customer (All)</option>
                                            <option value="rented_original">Original in Customer</option>
                                            <option value="rented_replacement_service">Replacement - Service</option>
                                            <option value="rented_replacement_rbo">Replacement - RBO</option>
                                            <option value="rented_check_position">Check Rent Position</option>
                                            <option value="vendor_rent">Vendor Rent</option>
                                        </optgroup>
                                        <optgroup label="Service (External)">
                                            <option value="service_external">External (All)</option>
                                            <option value="service_external_original_with_replace">Ext: Original w/ Replace</option>
                                            <option value="service_external_original_no_replace">Ext: Original w/o Replace</option>
                                            <option value="service_external_rented_replacement">Ext: Rented Replacement</option>
                                            <option value="service_external_stock">Ext: Stock In Service</option>
                                        </optgroup>
                                        <optgroup label="Service (Internal)">
                                            <option value="service_internal">Internal (All)</option>
                                            <option value="service_internal_original_with_replace">Int: Original w/ Replace</option>
                                            <option value="service_internal_original_no_replace">Int: Original w/o Replace</option>
                                            <option value="service_internal_rented_replacement">Int: Rented Replacement</option>
                                            <option value="service_internal_stock">Int: Stock In Service</option>
                                        </optgroup>
                                        <optgroup label="Insurance">
                                            <option value="service_insurance">Insurance (All)</option>
                                            <option value="service_insurance_original_with_replace">Ins: Original w/ Replace</option>
                                            <option value="service_insurance_original_no_replace">Ins: Original w/o Replace</option>
                                            <option value="service_insurance_rented_replacement">Ins: Rented Replacement</option>
                                            <option value="service_insurance_stock">Ins: Stock In Service</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-3 flex gap-2">
                    <button @click="addRule(query)" class="text-xs flex items-center gap-1 bg-white border border-slate-300 px-3 py-1.5 rounded hover:bg-slate-50 font-medium text-slate-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Condition
                    </button>
                </div>
            </div>

        </div>

        <!-- Results Table -->
        <div class="flex-1 bg-white overflow-hidden flex flex-col min-h-0">
            <div class="flex-1 overflow-auto">
                <table class="w-full text-left border-collapse" style="table-layout: fixed;">
                    <thead class="bg-slate-50 sticky top-0 z-10 text-xs uppercase font-semibold text-slate-500">
                        <tr>
                            <th x-show="columns.lot_number.visible" :style="'width: ' + columns.lot_number.width + 'px'" class="relative p-4 border-b border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors select-none group">
                                <div @click="sortBy('lot_number')" class="flex items-center gap-1">Lot Number <span x-show="sortCol === 'lot_number'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                                <div @mousedown="startResize($event, 'lot_number')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.product.visible" :style="'width: ' + columns.product.width + 'px'" class="relative p-4 border-b border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors select-none group">
                                <div @click="sortBy('product')" class="flex items-center gap-1">Product <span x-show="sortCol === 'product'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                                <div @mousedown="startResize($event, 'product')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.location.visible" :style="'width: ' + columns.location.width + 'px'" class="relative p-4 border-b border-slate-100 cursor-pointer hover:bg-slate-100 transition-colors select-none group">
                                <div @click="sortBy('location')" class="flex items-center gap-1">Location <span x-show="sortCol === 'location'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                                <div @mousedown="startResize($event, 'location')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.on_hand_quantity.visible" :style="'width: ' + columns.on_hand_quantity.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Qty
                                <div @mousedown="startResize($event, 'on_hand_quantity')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.rental_type.visible" :style="'width: ' + columns.rental_type.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Type
                                <div @mousedown="startResize($event, 'rental_type')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.status.visible" :style="'width: ' + columns.status.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Rental Status
                                <div @mousedown="startResize($event, 'status')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.actual_start_rental.visible" :style="'width: ' + columns.actual_start_rental.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Start
                                <div @mousedown="startResize($event, 'actual_start_rental')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.actual_end_rental.visible" :style="'width: ' + columns.actual_end_rental.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                End
                                <div @mousedown="startResize($event, 'actual_end_rental')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.vehicle_role.visible" :style="'width: ' + columns.vehicle_role.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Role
                                <div @mousedown="startResize($event, 'vehicle_role')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.linked_vehicle.visible" :style="'width: ' + columns.linked_vehicle.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Linked
                                <div @mousedown="startResize($event, 'linked_vehicle')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                            <th x-show="columns.in_stock.visible" :style="'width: ' + columns.in_stock.width + 'px'" class="relative p-4 border-b border-slate-100 text-center select-none group">
                                Stock
                                <div @mousedown="startResize($event, 'in_stock')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 transition-colors"></div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td x-show="columns.lot_number.visible" class="p-4 font-mono text-sm font-medium text-indigo-600 break-words" x-text="item.lot_number"></td>
                                <td x-show="columns.product.visible" class="p-4 break-words">
                                    <div class="font-medium text-slate-800 text-sm" x-text="item.product"></div>
                                    <div class="text-xs text-slate-400" x-text="item.internal_reference || 'No Ref'"></div>
                                </td>
                                <td x-show="columns.location.visible" class="p-4 break-words">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800" x-text="item.location"></span>
                                </td>
                                <td x-show="columns.on_hand_quantity.visible" class="p-4 text-center font-bold text-slate-600" x-text="item.on_hand_quantity"></td>
                                <td x-show="columns.rental_type.visible" class="p-4 text-center">
                                    <span x-show="item.is_vendor_rent" class="px-2 py-1 rounded text-xs font-bold bg-cyan-100 text-cyan-700">Vendor</span>
                                    <span x-show="!item.is_vendor_rent" class="px-2 py-1 rounded text-xs font-bold bg-indigo-100 text-indigo-700">Owned</span>
                                </td>
                                <td x-show="columns.status.visible" class="p-4 text-center">
                                    <template x-if="item.rental_id">
                                        <div class="flex flex-col items-center">
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-amber-100 text-amber-700 mb-1 break-all" x-text="item.rental_id"></span>
                                            <span class="text-[10px] text-slate-400" x-text="item.rental_type"></span>
                                        </div>
                                    </template>
                                    <template x-if="!item.rental_id && item.in_stock">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-700">In Stock</span>
                                    </template>
                                    <template x-if="!item.rental_id && !item.in_stock">
                                        <span class="px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-500">-</span>
                                    </template>
                                </td>
                                <td x-show="columns.actual_start_rental.visible" class="p-4 text-center text-xs text-slate-500" x-text="formatDate(item.actual_start_rental)"></td>
                                <td x-show="columns.actual_end_rental.visible" class="p-4 text-center text-xs text-slate-500" x-text="formatDate(item.actual_end_rental)"></td>
                                <td x-show="columns.vehicle_role.visible" class="p-4 text-center">
                                    <span x-show="item.vehicle_role" class="px-2 py-0.5 rounded text-[10px] bg-slate-100 border border-slate-200" x-text="item.vehicle_role"></span>
                                </td>
                                <td x-show="columns.linked_vehicle.visible" class="p-4 text-center text-xs text-slate-400 font-mono" x-text="item.linked_vehicle || '-'"></td>
                                <td x-show="columns.in_stock.visible" class="p-4 text-center">
                                    <span class="w-2 h-2 inline-block rounded-full" :class="item.in_stock ? 'bg-green-500' : 'bg-red-200'"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0 && !isLoading">
                            <td :colspan="Object.values(columns).filter(c => c.visible).length" class="p-12 text-center text-slate-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <p class="text-lg font-medium">No results found</p>
                                    <p class="text-sm">Adjust filters to see results.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <button @click="prevPage()" :disabled="page <= 1" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-600 text-sm hover:bg-slate-50 disabled:opacity-50">Previous</button>
                <span class="text-sm text-slate-500" x-text="'Page ' + page"></span>
                <button @click="nextPage()" :disabled="items.length < perPage" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-600 text-sm hover:bg-slate-50 disabled:opacity-50">Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function queryBuilder() {
        return {
            isLoading: false,
            items: [],
            totalItems: 0,
            page: 1,
            perPage: 50,
            
            // Query State
            query: {
                operator: 'AND',
                rules: [
                    { field: 'lot_number', operator: 'contains', value: '' }
                ]
            },

            // Table State
            sortCol: 'lot_number',
            sortAsc: true,
            columns: {
                lot_number: { label: 'Lot Number', visible: true, width: 150 },
                product: { label: 'Product', visible: true, width: 250 },
                location: { label: 'Location', visible: true, width: 140 },
                on_hand_quantity: { label: 'Qty', visible: true, width: 60 },
                rental_type: { label: 'Type', visible: true, width: 80 },
                status: { label: 'Rental Status', visible: true, width: 140 },
                actual_start_rental: { label: 'Start', visible: true, width: 100 },
                actual_end_rental: { label: 'End', visible: true, width: 100 },
                vehicle_role: { label: 'Role', visible: true, width: 80 },
                linked_vehicle: { label: 'Linked', visible: true, width: 100 },
                in_stock: { label: 'Stock', visible: true, width: 60 }
            },
            resizingCol: null,
            startX: 0,
            startWidth: 0,

            init() {
                // Load prefs
                let saved = localStorage.getItem('total_stock_table_columns');
                if (saved) {
                    try {
                        let parsed = JSON.parse(saved);
                        for (let key in this.columns) {
                            if (parsed[key]) {
                                this.columns[key].visible = parsed[key].visible;
                                this.columns[key].width = parsed[key].width;
                            }
                        }
                    } catch (e) {}
                }
                
                this.$watch('columns', (val) => {
                    localStorage.setItem('total_stock_table_columns', JSON.stringify(val));
                }, { deep: true });

                window.addEventListener('mousemove', (e) => {
                    if (this.resizingCol) {
                        const diff = e.clientX - this.startX;
                        this.columns[this.resizingCol].width = Math.max(50, this.startWidth + diff);
                    }
                });
                window.addEventListener('mouseup', () => {
                    this.resizingCol = null;
                    document.body.style.cursor = 'default';
                });

                this.fetchData();
            },

            addRule(group) {
                group.rules.push({ field: 'lot_number', operator: 'contains', value: '' });
            },

            removeRule(group, index) {
                group.rules.splice(index, 1);
            },

            resetQuery() {
                this.query = { operator: 'AND', rules: [{ field: 'lot_number', operator: 'contains', value: '' }] };
                this.page = 1;
                this.fetchData();
            },
            
            startResize(e, colId) {
                this.resizingCol = colId;
                this.startX = e.clientX;
                this.startWidth = this.columns[colId].width;
                document.body.style.cursor = 'col-resize';
                e.preventDefault(); 
            },
            
            sortBy(col) {
                if (this.sortCol === col) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortCol = col;
                    this.sortAsc = true;
                }
                this.fetchData();
            },
            
            formatDate(dateStr) {
                if (!dateStr) return '-';
                return dateStr.substring(0, 10);
            },

            async fetchData() {
                this.isLoading = true;
                try {
                    const response = await fetch('{{ route('total.stock.filter') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            filters: this.query,
                            page: this.page,
                            perPage: this.perPage,
                            sortCol: this.sortCol,
                            sortAsc: this.sortAsc
                        })
                    });
                    
                    const data = await response.json();
                    this.items = data.data;
                    this.totalItems = data.total;
                    
                } catch (error) {
                    console.error('Error fetching data:', error);
                    alert('Failed to fetch data. Check console.');
                } finally {
                    this.isLoading = false;
                }
            },
            
            nextPage() {
                this.page++;
                this.fetchData();
            },
            
            prevPage() {
                if (this.page > 1) {
                    this.page--;
                    this.fetchData();
                }
            }
        }
    }
</script>
@endsection
