<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sentence;
use App\Models\SentenceCategory;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends ApiController
{
    public function index()
    {
        $productCategories = ProductCategory::all();
        $banners = Banner::where('is_active',1)->get();
        $products = Product::where('category_id',3)->get();

        $data =
        [
            'product_categories' => $productCategories,
            'banners' => $banners,
            'products' => $products
        ];

        return $this->successResponse($data,'',Response::HTTP_OK);
    }

    public function showBanner(Request $request)
    {
        $validator = Validator::make($request->all(),
        [
            'category_id' => "required"
        ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products = Product::where('category_id',$request->category_id)->get();

        return $this->successResponse($products,'',Response::HTTP_OK);
    }

    public function showProductsForBanner(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'category_id' => "required"
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $products = Product::where('category_id',$request->category_id)->get();

        return $this->successResponse($products,'',Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'search' => "required"
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products = Product::where('name','LIKE',"%$request->search%")->get();

        if ($products->isEmpty())
        {
            return $this->errorResponse('محصول مورد نظر شما یافت نشد',404);
        }

        return $this->successResponse($products,'',Response::HTTP_OK);
    }

    public function showSentenceCategories()
    {
        $sentences = SentenceCategory::withCount('sentences')->get();

        return $this->successResponse($sentences,'',Response::HTTP_OK);
    }

    public function showSentences(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'search' => "required"
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sentences = SentenceCategory::where('id',$request->category_id)->with(['sentences','children.sentences'])->get();

        return $this->successResponse($sentences,'',Response::HTTP_OK);
    }

    public function showProduct(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'product_id' => "required"
            ]);

        if ($validator->fails())
        {
            return $this->errorResponse($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $product = Product::where('id',$request->product_id)->first();

        $templates = Template::all();

        $data =
        [
            'product' => $product,
            'templates' => $templates
        ];

        if ( $product == null )
        {
            return $this->errorResponse('چنین محصولی یافت نشد',Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse($data,'',Response::HTTP_OK);

    }
}
