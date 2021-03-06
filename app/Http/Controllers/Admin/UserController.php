<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Services\Users\UserUpdateService;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Users\UserDeletionService;
use Pterodactyl\Http\Requests\Admin\UserFormRequest;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $creationService;

    /**
     * @var \Pterodactyl\Services\Users\UserDeletionService
     */
    protected $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Services\Users\UserUpdateService
     */
    protected $updateService;

    /**
     * UserController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                         $alert
     * @param \Pterodactyl\Services\Users\UserCreationService           $creationService
     * @param \Pterodactyl\Services\Users\UserDeletionService           $deletionService
     * @param \Illuminate\Contracts\Translation\Translator              $translator
     * @param \Pterodactyl\Services\Users\UserUpdateService             $updateService
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     */
    public function __construct(
        AlertsMessageBag $alert,
        UserCreationService $creationService,
        UserDeletionService $deletionService,
        Translator $translator,
        UserUpdateService $updateService,
        UserRepositoryInterface $repository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->translator = $translator;
        $this->updateService = $updateService;
    }

    /**
     * Display user index page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = $this->repository->search($request->input('query'))->getAllUsersWithCounts();

        return view('admin.users.index', ['users' => $users]);
    }

    /**
     * Display new user page.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.users.new');
    }

    /**
     * Display user view page.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\View\View
     */
    public function view(User $user)
    {
        return view('admin.users.view', ['user' => $user]);
    }

    /**
     * Delete a user from the system.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            throw new DisplayException($this->translator->trans('admin/user.exceptions.user_has_servers'));
        }

        $this->deletionService->handle($user);

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Create a user.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function store(UserFormRequest $request)
    {
        $user = $this->creationService->handle($request->normalize());
        $this->alert->success($this->translator->trans('admin/user.notices.account_created'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Update a user on the system.
     *
     * @param \Pterodactyl\Http\Requests\Admin\UserFormRequest $request
     * @param \Pterodactyl\Models\User                         $user
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update(UserFormRequest $request, User $user)
    {
        $this->updateService->handle($user->id, $request->normalize());
        $this->alert->success($this->translator->trans('admin/user.notices.account_updated'))->flash();

        return redirect()->route('admin.users.view', $user->id);
    }

    /**
     * Get a JSON response of users on the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function json(Request $request)
    {
        return $this->repository->filterUsersByQuery($request->input('q'));
    }
}
