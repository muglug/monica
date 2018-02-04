<?php

namespace App\Http\Controllers\Api;

use App\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Resources\Tag\Tag as TagResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiTagController extends ApiController
{
    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tags = auth()->user()->account->tags()
                        ->paginate($this->getLimitPerPage());

        return TagResource::collection($tags);
    }

    /**
     * Get the detail of a given tag.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $tag = Tag::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new TagResource($tag);
    }

    /**
     * Store the tag.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $tag = Tag::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $tag->account_id = auth()->user()->account->id;
        $tag->name_slug = str_slug($tag->name);
        $tag->save();

        return new TagResource($tag);
    }

    /**
     * Update the tag.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $tag = Tag::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $tag->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $tag->updateSlug();

        return new TagResource($tag);
    }

    /**
     * Delete a tag.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $tag = Tag::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $tag->contacts()->detach();

        $tag->delete();

        return $this->respondObjectDeleted($tag->id);
    }
}
