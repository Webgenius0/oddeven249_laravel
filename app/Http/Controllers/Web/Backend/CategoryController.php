<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $data = Category::latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($data) {
                    $checked = $data->status == "active" ? "checked" : "";
                    return '
                        <div class="form-check form-switch d-flex">
                            <input onclick="showStatusChangeAlert(' . $data->id . ')"
                                   type="checkbox"
                                   class="form-check-input status-toggle"
                                   id="switch' . $data->id . '"
                                   data-id="' . $data->id . '"
                                   name="status" ' . $checked . '>
                        </div>';
                })
               ->addColumn('action', function ($data) {
                   return '<div class="btn-group btn-group-sm" role="group">
              <a href="' . route('admin.category.edit', $data->id) . '" class="text-white btn btn-primary" title="Edit">
                <i class="fa fa-pencil"></i>
              </a>
              <button type="button" onclick="deleteCategory(' . $data->id . ')" class="text-white btn btn-danger" title="Delete">
                <i class="fa fa-trash-o"></i>
              </button>
            </div>';
               })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.category.index');
    }
    /**
         * Show the form for creating a new category.
         */
    public function create(): View
    {
        return view('backend.layouts.category.create');
    }
    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            Category::create([
                'name'   => $request->name,
                'status' => 'active',
            ]);
            return redirect()->route('admin.category.index')->with('t-success', 'Category created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Something went wrong!');
        }
    }
    public function edit(int $id): View | RedirectResponse
    {
        try {
            $data = Category::findOrFail($id);
            return view('backend.layouts.category.edit', compact('data'));
        } catch (Exception $e) {
            return redirect()->route('admin.category.index')->with('t-error', 'Category not found!');
        }
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $category = Category::findOrFail($id);
            $category->update(['name' => $request->name]);
            return redirect()->route('admin.category.index')->with('t-success', 'Category updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Update failed!');
        }
    }
    public function status(int $id): JsonResponse
    {
        $data = Category::findOrFail($id);
        $data->status = ($data->status == 'active') ? 'inactive' : 'active';
        $data->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($data->status) . ' Successfully.',
            'data'    => $data,
        ]);
    }
    public function destroy(int $id): RedirectResponse
    {
        try {
            Category::findOrFail($id)->delete();
            return redirect()->back()->with('t-success', 'Category deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Something went wrong!');
        }
    }
}
